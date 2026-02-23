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
}

