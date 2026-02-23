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

                DB::transaction(function () use ($parser, $sanitizer, $mbKey, $dedupeKey) {
                    // Ensure mailbox exists
                    $mailbox = Mailbox::firstOrCreate(['mailbox_key' => $mbKey]);

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

