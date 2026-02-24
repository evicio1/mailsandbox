<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('mailboxes.index') }}"
               class="text-slate-500 hover:text-white transition flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="min-w-0">
                <h1 class="page-title truncate">{{ $mailbox->mailbox_key }}</h1>
                <p class="page-subtitle">{{ $messages->total() }} {{ Str::plural('message', $messages->total()) }}</p>
            </div>
        </div>
    </x-slot>

    <div class="animate-fade-in">
        <div class="card overflow-hidden">
            @if($messages->isEmpty())
                <div class="text-center py-16">
                    <div class="w-16 h-16 rounded-2xl bg-surface-700 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <p class="text-slate-400 font-medium">No messages yet</p>
                    <p class="text-slate-600 text-sm mt-1">Emails will appear here once imported</p>
                </div>
            @else
                <div class="divide-y divide-surface-700/60">
                    @foreach($messages as $msg)
                    @php
                        $initials = strtoupper(substr($msg->from_name ?: $msg->from_email, 0, 2));
                    @endphp
                    <a href="{{ route('messages.show', $msg->id) }}"
                       class="flex items-center gap-4 px-5 py-4 hover:bg-surface-750/50 transition group {{ !$msg->is_read ? 'border-l-2 border-l-brand-500 bg-brand-600/5' : '' }}">

                        <!-- Avatar -->
                        <div class="avatar-initials flex-shrink-0 text-xs">{{ $initials }}</div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline justify-between gap-4">
                                <span class="text-sm {{ !$msg->is_read ? 'font-semibold text-white' : 'font-medium text-slate-300' }} truncate">
                                    {{ $msg->from_name ?: $msg->from_email }}
                                </span>
                                <span class="text-xs text-slate-500 flex-shrink-0">
                                    {{ $msg->received_at->format('M j, g:i A') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-sm {{ !$msg->is_read ? 'text-slate-200' : 'text-slate-400' }} truncate">
                                    {{ $msg->subject ?: '(No Subject)' }}
                                </span>
                                @if($msg->snippet)
                                <span class="text-xs text-slate-600 truncate hidden sm:block">
                                    — {{ Str::limit($msg->snippet, 60) }}
                                </span>
                                @endif
                            </div>
                        </div>

                        <!-- Unread dot -->
                        @if(!$msg->is_read)
                        <div class="w-2 h-2 rounded-full bg-brand-500 flex-shrink-0"></div>
                        @endif
                    </a>
                    @endforeach
                </div>

                @if($messages->hasPages())
                <div class="px-5 py-4 border-t border-surface-700">
                    {{ $messages->links() }}
                </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
