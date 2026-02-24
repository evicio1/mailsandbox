<x-app-layout>
    <x-slot name="header">
        <div class="page-header mb-0">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Welcome back, {{ Auth::user()->name }}!</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 animate-fade-in">

        <!-- Stats Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <!-- Mailboxes -->
            <div class="stat-card">
                <div>
                    <div class="stat-card-label">Mailboxes</div>
                    <div class="stat-card-value">{{ $stats['mailboxes'] ?? '—' }}</div>
                    <div class="text-xs text-slate-500 mt-1">Active virtual inboxes</div>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: rgba(99,102,241,0.15)">
                    <svg class="w-6 h-6 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </div>
            </div>

            <!-- Total Messages -->
            <div class="stat-card">
                <div>
                    <div class="stat-card-label">Total Messages</div>
                    <div class="stat-card-value">{{ $stats['messages'] ?? '—' }}</div>
                    <div class="text-xs text-slate-500 mt-1">All imported emails</div>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: rgba(16,185,129,0.15)">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>

            <!-- Unread -->
            <div class="stat-card">
                <div>
                    <div class="stat-card-label">Unread</div>
                    <div class="stat-card-value text-brand-400">{{ $stats['unread'] ?? '—' }}</div>
                    <div class="text-xs text-slate-500 mt-1">Messages awaiting review</div>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: rgba(139,92,246,0.15)">
                    <svg class="w-6 h-6 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Quick access: mailboxes -->
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-white">Recent Mailboxes</h2>
                <a href="{{ route('mailboxes.index') }}" class="text-xs text-brand-400 hover:text-brand-300 transition">
                    View all →
                </a>
            </div>

            @if(isset($recentMailboxes) && $recentMailboxes->isNotEmpty())
            <div class="space-y-2">
                @foreach($recentMailboxes as $mb)
                <a href="{{ route('mailboxes.show', $mb->id) }}"
                   class="flex items-center justify-between p-3 rounded-lg hover:bg-surface-750 transition group">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-brand-600/20 text-brand-400 group-hover:bg-brand-600/30 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-white">{{ $mb->mailbox_key }}</div>
                            <div class="text-xs text-slate-500">Updated {{ $mb->updated_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-600 group-hover:text-slate-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                @endforeach
            </div>
            @else
            <div class="text-center py-10">
                <div class="w-16 h-16 rounded-2xl bg-surface-700 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </div>
                <p class="text-slate-400 text-sm font-medium">No mailboxes yet</p>
                <p class="text-slate-600 text-xs mt-1">Emails will appear here once the importer runs</p>
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
