<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\Mailbox;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;

class CleanupOldMessagesCommand extends Command
{
    protected $signature = 'app:cleanup-old-messages {--days=14 : Number of days to retain messages}';
    protected $description = 'Clean up messages older than the specified number of days';

    public function handle()
    {
        $days = (int) $this->option('days');
        
        $this->info("Starting retention process. Deleting older than {$days} days...");
        Log::info("Starting retention process. Deleting older than {$days} days...");

        $cutoffDate = Carbon::now()->subDays($days);

        $oldMessagesQuery = Message::where('received_at', '<', $cutoffDate);
        $count = $oldMessagesQuery->count();

        if ($count === 0) {
            $this->info("No messages to delete.");
            Log::info("No messages to delete.");
            return Command::SUCCESS;
        }

        $this->info("Found {$count} messages to delete.");
        Log::info("Found {$count} messages to delete.");

        try {
            $deletedAttCount = 0;
            
            // Chunking prevents memory overflow
            $oldMessagesQuery->chunk(100, function ($messages) use (&$deletedAttCount) {
                foreach ($messages as $msg) {
                    $attachments = $msg->attachments;
                    
                    foreach ($attachments as $att) {
                        if (Storage::exists($att->storage_path)) {
                            Storage::delete($att->storage_path);
                        }
                        $att->delete();
                        $deletedAttCount++;
                    }
                    
                    $msgAttachFolder = 'attachments/' . $msg->id;
                    if (Storage::exists($msgAttachFolder)) {
                        Storage::deleteDirectory($msgAttachFolder);
                    }
                    
                    $msg->delete();
                }
            });

            // Delete orphaned mailboxes
            $orphansDeleted = Mailbox::whereDoesntHave('messages')->delete();

            $summary = "Successfully deleted {$count} messages and {$deletedAttCount} attachments. Cleaned up {$orphansDeleted} empty mailboxes.";
            $this->info($summary);
            Log::info($summary);

        } catch (Exception $e) {
            $errorMsg = "Error during retention: " . $e->getMessage();
            $this->error($errorMsg);
            Log::error($errorMsg);
            return Command::FAILURE;
        }

        $this->info("Retention process finished.");
        return Command::SUCCESS;
    }
}

