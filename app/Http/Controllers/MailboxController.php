<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mailbox;

class MailboxController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('q');
        $user   = auth()->user();
        $tenant = $user->tenant;

        // Scope to tenant's mailboxes; fall back to all if user has no tenant
        $query = $tenant
            ? $tenant->mailboxes()
            : Mailbox::query();

        if ($search) {
            $query->where('mailbox_key', 'LIKE', '%' . $search . '%');
        }

        $mailboxes = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('mailboxes.index', compact('mailboxes', 'search'));
    }

    public function show(Mailbox $mailbox)
    {
        // Ensure the mailbox belongs to the current user's tenant
        $tenant = auth()->user()->tenant;
        if ($tenant && $mailbox->tenant_id !== null && $mailbox->tenant_id !== $tenant->id) {
            abort(403);
        }

        $messages = $mailbox->messages()->orderBy('received_at', 'desc')->orderBy('id', 'desc')->paginate(50);
        return view('mailboxes.show', compact('mailbox', 'messages'));
    }
}
