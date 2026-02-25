<?php

namespace App\Http\Controllers;

use App\Models\ExternalMailbox;
use Illuminate\Http\Request;
use Exception;

class ExternalMailboxController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;
        $mailboxes = $tenant->externalMailboxes()->latest()->get();

        return view('external-mailboxes.index', compact('tenant', 'mailboxes'));
    }

    public function create()
    {
        return view('external-mailboxes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['required', 'string', 'in:ssl,tls,none'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $tenant = auth()->user()->tenant;

        $tenant->externalMailboxes()->create([
            'email' => $validated['email'],
            'host' => $validated['host'],
            'port' => $validated['port'],
            'encryption' => $validated['encryption'],
            'username' => $validated['username'],
            'password' => $validated['password'], 
            'status' => 'active',
        ]);

        return redirect()->route('external-mailboxes.index')->with('success', 'External mailbox added successfully.');
    }

    public function edit(ExternalMailbox $externalMailbox)
    {
        if ($externalMailbox->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        return view('external-mailboxes.edit', compact('externalMailbox'));
    }

    public function update(Request $request, ExternalMailbox $externalMailbox)
    {
        if ($externalMailbox->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['required', 'string', 'in:ssl,tls,none'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string'], // Only update if provided
        ]);

        $updateData = [
            'host' => $validated['host'],
            'port' => $validated['port'],
            'encryption' => $validated['encryption'],
            'username' => $validated['username'],
            'status' => 'active', // Reset status if they update credentials
            'last_error' => null,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = $validated['password'];
        }

        $externalMailbox->update($updateData);

        return redirect()->route('external-mailboxes.index')->with('success', 'External mailbox settings updated.');
    }

    public function destroy(ExternalMailbox $externalMailbox)
    {
        if ($externalMailbox->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $externalMailbox->delete();

        return redirect()->route('external-mailboxes.index')->with('success', 'External mailbox removed.');
    }

    public function testConnection(Request $request)
    {
        $validated = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['required', 'string', 'in:ssl,tls,none'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $encString = $validated['encryption'] === 'none' ? '' : '/' . $validated['encryption'];
        // Suppress novalidate-cert for typical user testing unless specified, but for simplicity we can add it to prevent strict failures
        $mailboxHost = '{' . $validated['host'] . ':' . $validated['port'] . '/imap' . $encString . '/novalidate-cert}INBOX';

        try {
            $inbox = @imap_open($mailboxHost, $validated['username'], $validated['password'], OP_HALFOPEN);
            if ($inbox) {
                imap_close($inbox);
                return response()->json(['success' => true, 'message' => 'Connection successful!']);
            }
            throw new Exception(imap_last_error());
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        }
    }
}
