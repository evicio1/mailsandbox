<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="page-title">Mailboxes</h1>
            <p class="page-subtitle">All virtual inboxes for your team</p>
        </div>
    </x-slot>

    <div class="animate-fade-in space-y-5">

        <!-- Search -->
        <form action="{{ route('mailboxes.index') }}" method="GET" class="flex gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                </div>
                <input type="text" name="q" value="{{ $search }}"
                       class="form-input pl-10"
                       placeholder="Search mailbox e.g. qa+login …">
            </div>
            <button type="submit" class="btn-primary">Search</button>
        </form>

        <!-- Results -->
        <div class="card overflow-hidden">
            @if($mailboxes->isEmpty())
                <div class="text-center py-16">
                    <div class="w-16 h-16 rounded-2xl bg-surface-700 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <p class="text-slate-400 font-medium">No mailboxes found</p>
                    @if($search)
                        <p class="text-slate-600 text-sm mt-1">Try a different search term</p>
                        <a href="{{ route('mailboxes.index') }}" class="mt-4 inline-block text-sm text-brand-400 hover:text-brand-300">Clear search</a>
                    @endif
                </div>
            @else
                <div class="divide-y divide-surface-700/60">
                    @foreach($mailboxes as $mb)
                    <a href="{{ route('mailboxes.show', $mb->id) }}"
                       class="flex items-center justify-between px-5 py-4 hover:bg-surface-750/50 transition group">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center"
                                 style="background: rgba(99,102,241,0.12)">
                                <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-white text-sm">{{ $mb->mailbox_key }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Created {{ $mb->created_at->format('M j, Y') }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="badge-blue hidden sm:inline-flex">View Inbox</span>
                            <svg class="w-4 h-4 text-slate-600 group-hover:text-brand-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                    @endforeach
                </div>

                @if($mailboxes->hasPages())
                <div class="px-5 py-4 border-t border-surface-700">
                    {{ $mailboxes->appends(['q' => $search])->links() }}
                </div>
                @endif
            @endif
        </div>

    </div>
</x-app-layout>
