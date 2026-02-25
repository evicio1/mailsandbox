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

        $totalNew = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        // 1. Process Global Platform IMAP (from .env)
        $globalHost = env('IMAP_HOST');
        $globalUser = env('IMAP_USER');
        $globalPass = env('IMAP_PASS');

        if ($globalHost && $globalUser && $globalPass) {
            $this->info("Processing Global IMAP Account: $globalUser");
            $this->processMailbox($globalHost, 993, 'ssl', $globalUser, $globalPass, null, $sanitizer, $mailboxService, $totalNew, $totalSkipped, $totalErrors);
        } else {
            $this->warn("Global IMAP credentials not fully configured in .env. Skipping global account.");
        }

        // 2. Process Tenant External Mailboxes
        $externalMailboxes = \App\Models\ExternalMailbox::where('status', 'active')->get();
        
        foreach ($externalMailboxes as $extMb) {
            $this->info("Processing External Mailbox: {$extMb->email} (Tenant: {$extMb->tenant_id})");
            try {
                $this->processMailbox(
                    $extMb->host, 
                    $extMb->port, 
                    $extMb->encryption, 
                    $extMb->username, 
                    $extMb->password, 
                    $extMb->tenant_id, 
                    $sanitizer, 
                    $mailboxService, 
                    $totalNew, 
                    $totalSkipped, 
                    $totalErrors
                );

                $extMb->update([
                    'last_sync_at' => now(),
                    'last_error' => null
                ]);
            } catch (Exception $e) {
                $extMb->update([
                    'status' => 'failing',
                    'last_error' => $e->getMessage()
                ]);
                $this->error("Failed to process external mailbox {$extMb->email}: " . $e->getMessage());
                Log::error("Failed to process external mailbox {$extMb->id}: " . $e->getMessage());
            }
        }

        $summary = "Finished Global & External Sync. Imported: $totalNew, Skipped: $totalSkipped, Errors: $totalErrors";
        $this->info($summary);
        Log::info($summary);

        return Command::SUCCESS;
    }

    private function processMailbox($host, $port, $encryption, $username, $password, $forceTenantId, HtmlSanitizerService $sanitizer, MailboxService $mailboxService, &$newCount, &$skippedCount, &$errorCount)
    {
        $encString = $encryption === 'none' ? '' : '/' . $encryption;
        $connectionString = '{' . $host . ':' . $port . '/imap' . $encString . '/novalidate-cert}INBOX';

        $inbox = @imap_open($connectionString, $username, $password);

        if (!$inbox) {
            throw new Exception("IMAP Login Failed: " . imap_last_error());
        }

        $emails = imap_search($inbox, 'UNSEEN');
        if (!$emails) {
            imap_close($inbox);
            return;
        }

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

                DB::transaction(function () use ($parser, $sanitizer, $mbKey, $dedupeKey, $forceTenantId, $inbox, $email_number) {
                    
                    // Look for existing mailbox
                    $mailbox = Mailbox::where('mailbox_key', $mbKey)->first();

                    if (!$mailbox) {
                        if ($forceTenantId) {
                            // If this email came from an specifically configured external mailbox for a tenant, 
                            // we force-route this entirely under that tenant's catch-all. 
                            // It acts as a dynamic target inbox.
                            $mailbox = Mailbox::create([
                                'tenant_id' => $forceTenantId,
                                'mailbox_key' => $mbKey,
                                'status' => 'active'
                            ]);
                        } else {
                            // Legacy/Global Domain Routing Logic (Applies only to global .env account)
                            $domainPart = substr(strrchr($mbKey, "@"), 1) ?: '';
                            $domainModel = \App\Models\Domain::where('domain', $domainPart)
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
                                    throw new \Exception("Mailbox doesn't exist and catch-all is disabled for domain: $domainPart");
                                }
                            } else {
                                // Unregistered domain, fallback catch-all
                                $mailbox = Mailbox::create([
                                    'mailbox_key' => $mbKey,
                                    'status' => 'active'
                                ]);
                            }
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
                Log::error($errorMsg);
            }
        }

        imap_close($inbox);
    }
}

