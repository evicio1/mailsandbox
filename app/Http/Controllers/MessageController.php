<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Services\OtpDetectorService;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $tenant = $user->tenant;
        
        $query = Message::query()
            ->with(['mailbox', 'attachments', 'tags'])
            ->orderBy('received_at', 'desc');

        if ($tenant) {
            $query->whereIn('mailbox_id', $tenant->mailboxes()->pluck('id'));
        }

        // Apply filters
        $filters = $request->only(['mailbox_id', 'sender', 'subject', 'date_from', 'date_to', 'has_attachment', 'q', 'tag_id']);

        if (!empty($filters['mailbox_id'])) {
            $query->where('mailbox_id', $filters['mailbox_id']);
        }
        
        if (!empty($filters['sender'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('from_email', 'LIKE', '%' . $filters['sender'] . '%')
                  ->orWhere('from_name', 'LIKE', '%' . $filters['sender'] . '%');
            });
        }
        
        if (!empty($filters['subject'])) {
            $query->where('subject', 'LIKE', '%' . $filters['subject'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('received_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('received_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['has_attachment'])) {
            $query->has('attachments');
        }

        if (!empty($filters['tag_id'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('tags.id', $filters['tag_id']);
            });
        }

        if (!empty($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('subject', 'LIKE', '%' . $filters['q'] . '%')
                  ->orWhere('text_body', 'LIKE', '%' . $filters['q'] . '%')
                  ->orWhere('html_body_sanitized', 'LIKE', '%' . $filters['q'] . '%');
            });
        }

        $messages = $query->paginate(25);
        
        $mailboxes = $tenant ? $tenant->mailboxes : \App\Models\Mailbox::all();
        $tags = $tenant ? $tenant->tags : collect();

        return view('messages.index', compact('messages', 'filters', 'mailboxes', 'tags'));
    }

    public function show(Message $message, OtpDetectorService $otpDetector)
    {
        if (!$message->is_read) {
            $message->update(['is_read' => true]);
        }

        $otpSourceText = !empty($message->text_body) ? $message->text_body : strip_tags($message->html_body_sanitized);
        $extractedOtp = $otpDetector->extractBestOtp($otpSourceText);

        return view('messages.show', compact('message', 'extractedOtp'));
    }
    public function raw(Message $message)
    {
        if (!$message->raw_file_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($message->raw_file_path)) {
            abort(404, 'Raw source not available.');
        }

        return response()->file(\Illuminate\Support\Facades\Storage::disk('local')->path($message->raw_file_path), [
            'Content-Type' => 'text/plain',
        ]);
    }
}

