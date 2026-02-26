<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExternalMailbox;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ExternalMailboxController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('super_admin'),
        ];
    }

    public function index()
    {
        $mailboxes = ExternalMailbox::with('tenant')->latest('last_sync_at')->paginate(20);
        return view('admin.external-mailboxes.index', compact('mailboxes'));
    }

    public function toggleSync(ExternalMailbox $externalMailbox)
    {
        $externalMailbox->update([
            'is_sync_enabled' => !$externalMailbox->is_sync_enabled
        ]);

        return redirect()->back()->with('success', 'Mailbox sync status updated.');
    }

    public function logs(ExternalMailbox $externalMailbox)
    {
        $logs = $externalMailbox->syncLogs()->latest('started_at')->paginate(20);
        return view('admin.external-mailboxes.logs', compact('externalMailbox', 'logs'));
    }
}
