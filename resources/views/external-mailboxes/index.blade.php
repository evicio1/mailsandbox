<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h1 class="page-title">External Mailboxes</h1>
                <p class="page-subtitle">Sync emails from your external Catch-All accounts via IMAP</p>
            </div>
            <a href="{{ route('external-mailboxes.create') }}" class="btn-primary">
                Add Mailbox
            </a>
        </div>
    </x-slot>

    <div class="space-y-5 animate-fade-in">
        @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-emerald-900/30 border border-emerald-700/50 text-emerald-400 rounded-xl text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Email Address</th>
                        <th>Connection</th>
                        <th>Status</th>
                        <th>Last Sync</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mailboxes as $mailbox)
                    <tr>
                        <td class="font-medium text-white">{{ $mailbox->email }}</td>
                        <td class="text-slate-400">
                            {{ $mailbox->host }}:{{ $mailbox->port }} <span class="capitalize text-xs opacity-75">({{ $mailbox->encryption }})</span>
                        </td>
                        <td>
                            @if($mailbox->status === 'active')
                                <span class="badge-green">Active</span>
                            @else
                                <span class="badge-red" title="{{ $mailbox->last_error }}">Failing</span>
                            @endif
                        </td>
                        <td class="text-slate-400">
                            {{ $mailbox->last_sync_at ? $mailbox->last_sync_at->diffForHumans() : 'Never' }}
                        </td>
                        <td>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('external-mailboxes.edit', $mailbox) }}" class="text-xs text-brand-400 hover:text-brand-300 font-medium transition">Edit</a>
                                <form method="POST" action="{{ route('external-mailboxes.destroy', $mailbox) }}" onsubmit="return confirm('Stop syncing emails from this mailbox?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-red-400 hover:text-red-300 font-medium transition">Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-slate-500">
                            No external mailboxes added yet.<br>
                            <a href="{{ route('external-mailboxes.create') }}" class="text-brand-400 hover:underline mt-2 inline-block">Connect your first mailbox</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
