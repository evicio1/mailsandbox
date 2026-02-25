<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mailbox;
use App\Models\Message;
use App\Models\Attachment;
use App\Services\HtmlSanitizerService;
use App\Services\MailboxService;
use App\Services\MailParserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ImapImportCommand extends Command
{
    protected $signature = 'imap:import';
    protected $description = 'Import unseen emails via IMAP into the local database';

    public function handle(HtmlSanitizerService $sanitizer, MailboxService $mailboxService)
    {
        $this->info("Starting IMAP import...");
        Log::info("Starting IMAP import...");

        $mailboxHost = env('IMAP_HOST');
        $username = env('IMAP_USER');
        $password = env('IMAP_PASS');

        if (!$mailboxHost || !$username || !$password) {
            $this->error("IMAP credentials are not fully configured in .env");
            return Command::FAILURE;
        }

        $inbox = @imap_open($mailboxHost, $username, $password);

        if (!$inbox) {
            $errorMsg = "Failed to connect to IMAP: " . imap_last_error();
            $this->error($errorMsg);
            Log::error($errorMsg);
            return Command::FAILURE;
        }

        $emails = imap_search($inbox, 'UNSEEN');
        if (!$emails) {
            $this->info("No new emails found.");
            imap_close($inbox);
            return Command::SUCCESS;
        }

        $newCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($emails as $email_number) {
            try {
                $parser = new MailParserService($inbox, $email_number);
                $parser->parse();

                $mbKey = MailboxService::normalizeMailboxKey($parser->getTargetMailbox());
                
                $dedupeKey = MailboxService::generateDedupeKey(
                    $mbKey,
                    $parser->messageId,
                    $parser->fromEmail,
                    $parser->subject,
                    $parser->receivedAt,
                    $parser->sizeBytes
                );

                // Check if exists
                if (Message::where('dedupe_key', $dedupeKey)->exists()) {
                    $skippedCount++;
                    imap_setflag_full($inbox, $email_number, "\\Seen");
                    continue;
                }

                // Domain Routing Logic
                $domainPart = substr(strrchr($mbKey, "@"), 1) ?: '';
                $domainModel = \App\Models\Domain::where('domain', $domainPart)
                    ->where('is_verified', true)
                    ->first();

                DB::transaction(function () use ($parser, $sanitizer, $mbKey, $dedupeKey, $domainModel, $inbox, $email_number) {
                    $tenantId = $domainModel ? $domainModel->tenant_id : null;
                    
                    // Look for existing mailbox
                    $mailbox = Mailbox::where('mailbox_key', $mbKey)->first();

                    if (!$mailbox) {
                        if ($domainModel) {
                            // Belongs to a known domain
                            if ($domainModel->catch_all_enabled) {
                                // Create the mailbox dynamically under the tenant
                                $mailbox = Mailbox::create([
                                    'tenant_id' => $tenantId,
                                    'mailbox_key' => $mbKey,
                                    'status' => 'active'
                                ]);
                            } else {
                                // Domain exists but catch-all is disabled and no specific mailbox exists.
                                // We should reject/ignore this email.
                                throw new \Exception("Mailbox doesn't exist and catch-all is disabled for domain: $domainPart");
                            }
                        } else {
                            // Legacy behavior: If domain not registered, just create a global mailbox without tenant,
                            // or maybe we should reject it. For MVP, we will still ingest it to avoid breaking the old catch-all evicio.site.
                            $mailbox = Mailbox::create([
                                'mailbox_key' => $mbKey,
                                'status' => 'active'
                            ]);
                        }
                    }

                    $htmlSanitized = $sanitizer->sanitize($parser->htmlBody);
                    $snippet = mb_substr(trim($parser->textBody), 0, 150);

                    $message = Message::create([
                        'mailbox_id' => $mailbox->id,
                        'dedupe_key' => $dedupeKey,
                        'message_id' => $parser->messageId,
                        'subject'    => $parser->subject,
                        'from_name'  => $parser->fromName,
                        'from_email' => $parser->fromEmail,
                        'to_raw'     => $parser->toRaw,
                        'cc_raw'     => $parser->ccRaw,
                        'received_at'=> clone new \DateTime($parser->receivedAt),
                        'snippet'    => $snippet,
                        'text_body'  => $parser->textBody,
                        'html_body_sanitized' => $htmlSanitized,
                        'headers_raw'=> $parser->headersRaw,
                        'size_bytes' => $parser->sizeBytes,
                        'is_read'    => false,
                    ]);

                    // Process attachments
                    if (!empty($parser->attachments)) {
                        $msgAttachFolder = 'attachments/' . $message->id;
                        Storage::makeDirectory($msgAttachFolder);

                        foreach ($parser->attachments as $att) {
                            $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $att['filename']);
                            if (empty($safeName)) {
                                $safeName = 'attachment_' . time();
                            }
                            
                            $filePath = $msgAttachFolder . '/' . $safeName;
                            Storage::put($filePath, $att['content']);

                            Attachment::create([
                                'message_id'   => $message->id,
                                'filename'     => $att['filename'],
                                'content_type' => $att['type'],
                                'size_bytes'   => $att['size'],
                                'storage_path' => $filePath,
                            ]);
                        }
                    }
                });

                $newCount++;
                imap_setflag_full($inbox, $email_number, "\\Seen");

            } catch (Exception $e) {
                $errorCount++;
                $errorMsg = "Error processing msg $email_number: " . $e->getMessage();
                $this->error($errorMsg);
                Log::error($errorMsg);
            }
        }

        imap_close($inbox);

        $summary = "Finished. Imported: $newCount, Skipped: $skippedCount, Errors: $errorCount";
        $this->info($summary);
        Log::info($summary);

        return Command::SUCCESS;
    }
}

