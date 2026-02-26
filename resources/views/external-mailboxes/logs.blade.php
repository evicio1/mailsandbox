<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('external-mailboxes.index') }}" class="text-slate-400 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="page-title">Sync Logs</h1>
                <p class="page-subtitle">{{ $externalMailbox->email }}</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4 animate-fade-in">
        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Started At</th>
                        <th>Status</th>
                        <th>Duration</th>
                        <th>Processed</th>
                        <th>Imported</th>
                        <th>Errors</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap">{{ $log->started_at->format('M d, Y H:i:s') }}</td>
                        <td>
                            @if($log->status === 'success')
                                <span class="badge-green">Success</span>
                            @elseif($log->status === 'processing')
                                <span class="badge-blue">Processing</span>
                            @else
                                <span class="badge-red">Failed</span>
                            @endif
                        </td>
                        <td class="text-slate-400">
                            {{ $log->finished_at ? $log->started_at->diffInSeconds($log->finished_at) . 's' : '-' }}
                        </td>
                        <td class="text-slate-300 font-medium">
                            {{ number_format($log->emails_found) }}
                        </td>
                        <td class="text-emerald-400 font-medium">
                            +{{ number_format($log->emails_imported) }}
                        </td>
                        <td class="text-slate-400 text-sm max-w-xs truncate" title="{{ $log->error_message }}">
                            {{ $log->error_message ?: '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 text-slate-500">
                            No sync logs available yet for this mailbox.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-2">
            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
