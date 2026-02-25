<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Services\OtpDetectorService;

class MessageController extends Controller
{
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

