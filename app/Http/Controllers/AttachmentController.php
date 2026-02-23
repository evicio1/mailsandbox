<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function download(Attachment $attachment)
    {
        if (!Storage::exists($attachment->storage_path)) {
            abort(404, 'File not found on disk.');
        }

        return Storage::download($attachment->storage_path, $attachment->filename, [
            'Content-Type' => $attachment->content_type ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }
}

