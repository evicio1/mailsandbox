<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Mailbox;
use App\Models\Message;
use App\Models\Attachment;
use App\Models\ExternalMailbox;
use App\Models\Tenant;
use App\Notifications\ExternalMailboxFailingNotification;
use App\Services\HtmlSanitizerService;
use App\Services\MailboxService;
use App\Services\MailParserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
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
            try {
                $this->processMailbox($globalHost, env('IMAP_PORT', 993), env('IMAP_ENCRYPTION', 'ssl'), 'INBOX', $globalUser, $globalPass, null, $sanitizer, $mailboxService, $totalNew, $totalSkipped, $totalErrors);
            } catch (Exception $e) {
                $this->error("Failed to process Global IMAP account: " . $e->getMessage());
                Log::error("Failed to process Global IMAP account: " . $e->getMessage());
            }
        } else {
            $this->warn("Global IMAP credentials not fully configured in .env. Skipping global account.");
        }

        // 2. Process Tenant External Mailboxes (with locking and batching)
        $externalMailboxes = ExternalMailbox::where('status', 'active')
            ->where('is_sync_enabled', true)
            ->where(function ($q) {
                $q->whereNull('sync_lock_until')
                  ->orWhere('sync_lock_until', '<=', now());
            })
            ->orderBy('last_sync_at')
            ->take(3)
            ->get();
        
        foreach ($externalMailboxes as $extMb) {
            $this->info("Processing External Mailbox: {$extMb->email} (Tenant: {$extMb->tenant_id})");
            
            // Set lock proactively
            $extMb->update(['sync_lock_until' => now()->addMinutes(10)]);

            $syncLog = $extMb->syncLogs()->create([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            $mbNew = 0;
            $mbSkipped = 0;
            $mbErrors = 0;

            try {
                $this->processMailbox(
                    $extMb->host, 
                    $extMb->port, 
                    $extMb->encryption, 
                    $extMb->folder ?? 'INBOX',
                    $extMb->username, 
                    $extMb->password, 
                    $extMb, 
                    $sanitizer, 
                    $mailboxService, 
                    $mbNew, 
                    $mbSkipped, 
                    $mbErrors
                );

                $totalNew += $mbNew;
                $totalSkipped += $mbSkipped;
                $totalErrors += $mbErrors;

                $syncLog->update([
                    'status' => 'success',
                    'emails_found' => ($mbNew + $mbSkipped + $mbErrors),
                    'emails_imported' => $mbNew,
                    'finished_at' => now(),
                ]);

                $extMb->update([
                    'last_sync_at' => now(),
                    'last_error' => null,
                    'error_count' => 0,
                    'sync_lock_until' => null // release lock
                ]);
            } catch (Exception $e) {
                $totalNew += $mbNew;
                $totalSkipped += $mbSkipped;
                $totalErrors += $mbErrors;

                $syncLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'emails_found' => ($mbNew + $mbSkipped + $mbErrors),
                    'emails_imported' => $mbNew,
                    'finished_at' => now(),
                ]);

                $newErrorCount = $extMb->error_count + 1;
                $backoffMinutes = pow(5, min($newErrorCount, 4)); // 5, 25, 125, 625 mins
                
                $updateData = [
                    'last_error' => $e->getMessage(),
                    'error_count' => $newErrorCount,
                    'sync_lock_until' => now()->addMinutes($backoffMinutes)
                ];

                if ($newErrorCount >= 5 && $extMb->status === 'active') {
                    $updateData['status'] = 'failing';
                    
                    $tenant = Tenant::find($extMb->tenant_id);
                    if ($tenant && $tenant->owner) {
                        $tenant->owner->notify(new ExternalMailboxFailingNotification($extMb));
                    }
                }

                $extMb->update($updateData);

                $this->error("Failed to process external mailbox {$extMb->email}: " . $e->getMessage());
                Log::error("Failed to process external mailbox {$extMb->id}: " . $e->getMessage());
            }
        }

        $summary = "Finished Global & External Sync. Imported: $totalNew, Skipped: $totalSkipped, Errors: $totalErrors";
        $this->info($summary);
        Log::info($summary);

        return Command::SUCCESS;
    }

    private function processMailbox($host, $port, $encryption, $folder, $username, $password, ?ExternalMailbox $extMb, HtmlSanitizerService $sanitizer, MailboxService $mailboxService, &$newCount, &$skippedCount, &$errorCount)
    {
        $encString = $encryption === 'none' ? '' : '/' . $encryption;
        $connectionString = '{' . $host . ':' . $port . '/imap' . $encString . '/novalidate-cert}' . $folder;

        $inbox = @imap_open($connectionString, $username, $password);

        if (!$inbox) {
            throw new Exception("IMAP Login Failed: " . imap_last_error());
        }

        if ($extMb) {
            // Incremental sync via filtering ALL UIDs in memory
            $allUids = imap_search($inbox, 'ALL', SE_UID) ?: [];
            
            $this->info("Debug: ExtMB last_seen_uid is " . $extMb->last_seen_uid);
            $this->info("Debug: allUids length: " . count($allUids));
            if (count($allUids)) {
                $this->info("Debug: allUids: " . implode(',', $allUids));
            }

            // Filter out UIDs we've already seen
            $emails = array_filter($allUids, function($uid) use ($extMb) {
                return $uid > $extMb->last_seen_uid;
            });
            
            // Re-index array
            $emails = array_values($emails);
        } else {
            // Global fallback
            $emails = imap_search($inbox, 'UNSEEN');
        }

        if (!$emails) {
            imap_close($inbox);
            return;
        }

        // Limit to 50 emails per run to prevent timeout
        $emails = array_slice($emails, 0, 50);

        foreach ($emails as $email_identifier) {
            // If using UID search, we must get the message number for the parser
            $email_number = $extMb ? imap_msgno($inbox, $email_identifier) : $email_identifier;
            $uid = $extMb ? $email_identifier : imap_uid($inbox, $email_number);
            try {
                $parser = new MailParserService($inbox, $email_number);
                $parser->parse();

                if ($extMb && $extMb->domain) {
                    $parser->setCustomDomains([$extMb->domain]);
                }

                $mbKey = MailboxService::normalizeMailboxKey($parser->getTargetMailbox());
                
                // Determine dedupe key. ExtMb ID is used to prevent cross-source clashes if domain logic isn't enough.
                $extMbPrefix = $extMb ? $extMb->id . '-' : 'G-';
                $dedupeKey = $extMbPrefix . MailboxService::generateDedupeKey(
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
                    if ($extMb) {
                        $extMb->update(['last_seen_uid' => max($extMb->last_seen_uid, $uid)]);
                    } else {
                        @imap_setflag_full($inbox, $email_number, "\\Seen");
                    }
                    continue;
                }

                DB::transaction(function () use ($parser, $sanitizer, $mbKey, $dedupeKey, $extMb, $inbox, $email_number) {
                    
                    // Look for existing mailbox
                    $mailbox = Mailbox::where('mailbox_key', $mbKey)->first();

                    if (!$mailbox) {
                        if ($extMb) {
                            $tenant = Tenant::find($extMb->tenant_id);
                            
                            if ($tenant && $tenant->mailboxes()->active()->count() < $tenant->inbox_limit) {
                                $mailbox = Mailbox::create([
                                    'tenant_id' => $extMb->tenant_id,
                                    'mailbox_key' => $mbKey,
                                    'status' => 'active'
                                ]);
                            } else {
                                // Quota exceeded. Route to a designated "Unassigned" mailbox or fail safe.
                                // For now, we will create/use an "unassigned" fallback for this tenant.
                                $mailbox = Mailbox::firstOrCreate(
                                    ['tenant_id' => $extMb->tenant_id, 'mailbox_key' => 'unassigned@' . ($extMb->domain ?? 'external.local')],
                                    ['status' => 'active']
                                );
                            }
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
                
                if ($extMb) {
                    $extMb->update(['last_seen_uid' => max($extMb->last_seen_uid, $uid)]);
                } else {
                    @imap_setflag_full($inbox, $email_number, "\\Seen");
                }

            } catch (Exception $e) {
                $errorCount++;
                $errorMsg = "Error processing msg $email_number: " . $e->getMessage();
                Log::error($errorMsg);
            }
        }

        imap_close($inbox);
    }
}

