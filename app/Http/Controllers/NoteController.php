<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Note;

class NoteController extends Controller
{
    public function store(Request $request, Message $message)
    {
        $tenant = auth()->user()->tenant;
        if (!$tenant || $message->mailbox->tenant_id !== $tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $message->notes()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        return back()->with('success', 'Note added.');
    }

    public function destroy(Note $note)
    {
        // Only allow if user is the author or a tenant admin
        $user = auth()->user();
        $isAuthor = $note->user_id === $user->id;
        $isTenantAdmin = $user->tenant_id === $note->message->mailbox->tenant_id && ($user->isTenantAdmin() || $user->isSuperAdmin());

        if (!$isAuthor && !$isTenantAdmin) {
            abort(403);
        }

        $note->delete();

        return back()->with('success', 'Note deleted.');
    }
}
