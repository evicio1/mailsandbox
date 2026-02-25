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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mailbox_key' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $tenant = $user->tenant;

        if (!$tenant) {
            abort(403, 'User does not belong to a tenant.');
        }

        if ($tenant->inbox_limit !== -1 && $tenant->mailboxes()->active()->count() >= $tenant->inbox_limit) {
            return response()->json([
                'error' => 'INBOX_QUOTA_EXCEEDED',
                'message' => 'You have reached your inbox quota limit.'
            ], 403);
        }

        $mailbox = $tenant->mailboxes()->firstOrCreate([
            'mailbox_key' => $validated['mailbox_key']
        ], [
            'status' => 'active'
        ]);

        if ($request->wantsJson()) {
            return response()->json($mailbox, 201);
        }

        return redirect()->route('mailboxes.show', $mailbox)->with('success', 'Mailbox created successfully.');
    }

    public function toggleStatus(Request $request, Mailbox $mailbox)
    {
        $tenant = auth()->user()->tenant;
        if ($tenant && $mailbox->tenant_id !== null && $mailbox->tenant_id !== $tenant->id) {
            abort(403);
        }

        if ($mailbox->status === 'active') {
            $mailbox->markAsDisabled();
            $message = 'Mailbox disabled successfully.';
        } else {
            // Check quota before enabling
            if ($tenant && $tenant->inbox_limit !== -1 && $tenant->mailboxes()->active()->count() >= $tenant->inbox_limit) {
                return back()->with('error', 'Cannot enable mailbox: Inbox quota exceeded.');
            }
            $mailbox->update(['status' => 'active']);
            $message = 'Mailbox enabled successfully.';
        }

        return back()->with('success', $message);
    }

    public function autoDisable(Request $request)
    {
        $tenant = auth()->user()->tenant;
        if (!$tenant) {
            abort(403);
        }

        if ($tenant->inbox_limit === -1) {
            return back()->with('success', 'Plan is unlimited, no mailboxes to disable.');
        }

        $activeCount = $tenant->mailboxes()->active()->count();
        $limit = $tenant->inbox_limit;

        if ($activeCount <= $limit) {
            return back()->with('success', 'You are already within your plan limit.');
        }

        $excess = $activeCount - $limit;
        
        $mailboxesToDisable = $tenant->mailboxes()
            ->active()
            ->orderBy('created_at', 'asc') // disable oldest first
            ->limit($excess)
            ->get();

        foreach ($mailboxesToDisable as $mailbox) {
            $mailbox->markAsDisabled();
        }

        return back()->with('success', "Automatically disabled $excess oldest inboxes to match your quota.");
    }
}
