<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">All External Mailboxes</h1>
        <p class="page-subtitle">Super Admin view of all synced mailboxes</p>
    </x-slot>

    <div class="space-y-4 animate-fade-in">
        @if(session('success'))
            <div class="p-4 bg-emerald-900/30 border border-emerald-700/50 text-emerald-400 rounded-xl text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Tenant</th>
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
                        <td class="text-slate-300">
                            {{ $mailbox->tenant->name ?? 'Unknown' }}
                        </td>
                        <td class="text-slate-400">
                            {{ $mailbox->host }}:{{ $mailbox->port }} <span class="capitalize text-xs opacity-75">({{ $mailbox->encryption }})</span>
                        </td>
                        <td>
                            <div class="flex flex-col gap-1 items-start">
                                @if($mailbox->status === 'active')
                                    <span class="badge-green">Active</span>
                                @else
                                    <span class="badge-red" title="{{ $mailbox->last_error }}">Failing</span>
                                @endif

                                @if($mailbox->is_sync_enabled)
                                    <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-blue-500/10 text-blue-400 ring-1 ring-inset ring-blue-500/20">Sync On</span>
                                @else
                                    <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-slate-500/10 text-slate-400 ring-1 ring-inset ring-slate-500/20">Sync Off</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-slate-400">
                            {{ $mailbox->last_sync_at ? $mailbox->last_sync_at->diffForHumans() : 'Never' }}
                        </td>
                        <td>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.external-mailboxes.logs', $mailbox) }}" class="text-xs text-blue-400 hover:text-blue-300 font-medium transition border-r border-surface-600 pr-3">Logs</a>
                                
                                <form method="POST" action="{{ route('admin.external-mailboxes.toggle-sync', $mailbox) }}">
                                    @csrf
                                    <button class="text-xs {{ $mailbox->is_sync_enabled ? 'text-amber-400 hover:text-amber-300' : 'text-emerald-400 hover:text-emerald-300' }} font-medium transition">
                                        {{ $mailbox->is_sync_enabled ? 'Pause Sync' : 'Resume Sync' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 text-slate-500">
                            No external mailboxes configured across all tenants.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-2">
            {{ $mailboxes->links() }}
        </div>
    </div>
</x-app-layout>
