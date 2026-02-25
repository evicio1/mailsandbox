<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;
use Carbon\Carbon;
use App\Models\Mailbox;
use App\Models\Message;
use App\Models\Attachment;
use App\Models\Domain;
use App\Services\HtmlSanitizerService;
use App\Services\MailboxService;

class InboundWebhookController extends Controller
{
    public function handle(Request $request, HtmlSanitizerService $sanitizer)
    {
        Log::info("Received inbound email webhook.");

        try {
            $recipient = $request->input('recipient', '');
            $sender = $request->input('sender', '');
            $subject = $request->input('subject', '');
            $from = $request->input('from', '');
            $timestamp = $request->input('timestamp', time());
            $receivedAt = Carbon::createFromTimestamp($timestamp);
            
            $bodyPlain = $request->input('body-plain', '');
            $bodyHtml = $request->input('body-html', '');
            $messageId = $request->input('Message-Id', '');
            
            $headersRawJson = $request->input('message-headers', '[]');
            $headersArr = json_decode($headersRawJson, true) ?: [];
            
            // Reconstruct headers as raw string for DB
            $headersRaw = '';
            $toRaw = [];
            $ccRaw = [];
            $bccRaw = [];
            $spfResult = null;
            $dkimResult = null;
            $spamScore = null;

            foreach ($headersArr as $headerGroup) {
                if (is_array($headerGroup) && count($headerGroup) == 2) {
                    $key = $headerGroup[0];
                    $val = $headerGroup[1];
                    $headersRaw .= "$key: $val\r\n";
                    
                    $lowerKey = strtolower($key);
                    if ($lowerKey === 'to') {
                        $toRaw[] = $val;
                    } elseif ($lowerKey === 'cc') {
                        $ccRaw[] = $val;
                    } elseif ($lowerKey === 'bcc') {
                        $bccRaw[] = $val;
                    } elseif ($lowerKey === 'x-mailgun-spf' || $lowerKey === 'received-spf') {
                        $spfResult = $val;
                    } elseif ($lowerKey === 'x-mailgun-dkim-check-result' || $lowerKey === 'dkim-signature') {
                        $dkimResult = $val;
                    } elseif ($lowerKey === 'x-mailgun-sspam-score') {
                        $spamScore = $val;
                    }
                }
            }

            if (empty($recipient)) {
                return response()->json(['error' => 'No recipient'], 400);
            }

            $mbKey = MailboxService::normalizeMailboxKey($recipient);
            $sizeBytes = strlen($request->getContent());

            $dedupeKey = MailboxService::generateDedupeKey(
                $mbKey,
                $messageId,
                $sender,
                $subject,
                $receivedAt->format('Y-m-d H:i:s'),
                $sizeBytes
            );

            // Check if exists
            if (Message::where('dedupe_key', $dedupeKey)->exists()) {
                Log::info("Webhook ignoring duplicate message: $dedupeKey");
                return response()->json(['status' => 'duplicate'], 200);
            }

            // Save raw MIME if provided
            $rawFilePath = null;
            if ($request->hasFile('body-mime')) {
                $rawFilePath = $request->file('body-mime')->store('raw_mime', 'local');
            } elseif ($request->filled('body-mime')) {
                // If text
                $mimeName = 'raw_mime/' . uniqid('mime_') . '.eml';
                Storage::disk('local')->put($mimeName, $request->input('body-mime'));
                $rawFilePath = $mimeName;
            }

            DB::transaction(function () use ($mbKey, $dedupeKey, $messageId, $subject, $sender, $toRaw, $ccRaw, $bccRaw, $receivedAt, $bodyPlain, $bodyHtml, $headersRaw, $sizeBytes, $sanitizer, $rawFilePath, $spfResult, $dkimResult, $spamScore, $request) {
                
                // Mailbox lookup
                $mailbox = Mailbox::where('mailbox_key', $mbKey)->first();

                if (!$mailbox) {
                    $domainPart = substr(strrchr($mbKey, "@"), 1) ?: '';
                    $domainModel = Domain::where('domain', $domainPart)
                        ->where('is_verified', true)
                        ->first();

                    if ($domainModel) {
                        if ($domainModel->catch_all_enabled) {
                            $mailbox = Mailbox::create([
                                'tenant_id' => $domainModel->tenant_id,
                                'mailbox_key' => $mbKey,
                                'status' => 'active'
                            ]);
                        } else {
                            throw new Exception("Mailbox doesn't exist and catch-all is disabled for domain: $domainPart");
                        }
                    } else {
                        // Unregistered domain fallback
                        $mailbox = Mailbox::create([
                            'mailbox_key' => $mbKey,
                            'status' => 'active'
                        ]);
                    }
                }

                $htmlSanitized = $sanitizer->sanitize($bodyHtml);
                $snippet = mb_substr(trim($bodyPlain), 0, 150);

                $message = Message::create([
                    'mailbox_id' => $mailbox->id,
                    'dedupe_key' => $dedupeKey,
                    'message_id' => $messageId,
                    'subject'    => $subject,
                    'from_name'  => $sender,
                    'from_email' => $sender,
                    'to_raw'     => $toRaw,
                    'cc_raw'     => $ccRaw,
                    'bcc_raw'    => $bccRaw,
                    'received_at'=> $receivedAt,
                    'snippet'    => $snippet,
                    'text_body'  => $bodyPlain,
                    'html_body_sanitized' => $htmlSanitized,
                    'headers_raw'=> $headersRaw,
                    'size_bytes' => $sizeBytes,
                    'is_read'    => false,
                    'raw_file_path' => $rawFilePath,
                    'spf_result' => $spfResult,
                    'dkim_result' => $dkimResult,
                    'spam_score' => $spamScore,
                ]);

                // Attachments
                $attachmentCount = (int)$request->input('attachment-count', 0);
                if ($attachmentCount > 0) {
                    $msgAttachFolder = 'attachments/' . $message->id;
                    Storage::makeDirectory($msgAttachFolder);

                    $contentIdMapStr = $request->input('content-id-map', '{}');
                    $contentIdMap = json_decode($contentIdMapStr, true) ?: [];

                    for ($i = 1; $i <= $attachmentCount; $i++) {
                        $fileKey = 'attachment-' . $i;
                        if ($request->hasFile($fileKey)) {
                            $file = $request->file($fileKey);
                            $filename = $file->getClientOriginalName();
                            $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
                            if (empty($safeName)) {
                                $safeName = 'attachment_' . time();
                            }

                            $filePath = $file->storeAs($msgAttachFolder, $safeName, 'local');

                            $cid = array_search($fileKey, $contentIdMap);
                            if ($cid) {
                                $cid = trim($cid, '<>');
                            }

                            Attachment::create([
                                'message_id'   => $message->id,
                                'filename'     => $filename,
                                'content_type' => $file->getClientMimeType(),
                                'size_bytes'   => $file->getSize(),
                                'storage_path' => $filePath,
                                'content_id'   => $cid ?: null,
                            ]);
                        }
                    }
                }
            });

            return response()->json(['status' => 'success'], 200);

        } catch (Exception $e) {
            Log::error("Webhook error: " . $e->getMessage());
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}
