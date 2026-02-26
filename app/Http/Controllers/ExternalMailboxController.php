<?php

namespace App\Http\Controllers;

use App\Models\ExternalMailbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
            'domain' => ['nullable', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['required', 'string', 'in:ssl,tls,none'],
            'folder' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $tenant = auth()->user()->tenant;

        $tenant->externalMailboxes()->create([
            'email' => $validated['email'],
            'domain' => $validated['domain'],
            'host' => $validated['host'],
            'port' => $validated['port'],
            'encryption' => $validated['encryption'],
            'folder' => $validated['folder'],
            'username' => $validated['username'],
            'password' => $validated['password'], 
            'status' => 'active',
            'is_sync_enabled' => true,
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
            'domain' => ['nullable', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['required', 'string', 'in:ssl,tls,none'],
            'folder' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string'], // Only update if provided
        ]);

        $updateData = [
            'domain' => $validated['domain'],
            'host' => $validated['host'],
            'port' => $validated['port'],
            'encryption' => $validated['encryption'],
            'folder' => $validated['folder'],
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

    public function toggleSync(ExternalMailbox $externalMailbox)
    {
        if ($externalMailbox->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $externalMailbox->update([
            'is_sync_enabled' => !$externalMailbox->is_sync_enabled
        ]);

        return redirect()->back()->with('success', 'Mailbox sync status updated.');
    }

    public function logs(ExternalMailbox $externalMailbox)
    {
        if ($externalMailbox->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $logs = $externalMailbox->syncLogs()->latest('started_at')->paginate(20);

        return view('external-mailboxes.logs', compact('externalMailbox', 'logs'));
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
        $key = 'test-connection:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false, 
                'message' => "Too many test attempts. Please wait {$seconds} seconds."
            ], 429);
        }

        RateLimiter::hit($key, 60); // 5 attempts per minute max

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
