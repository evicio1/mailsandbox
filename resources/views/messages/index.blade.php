<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="page-title">All Messages</h1>
            <p class="page-subtitle">Search and filter emails across your mailboxes</p>
        </div>
    </x-slot>

    <div class="animate-fade-in flex flex-col md:flex-row gap-6 items-start">

        <!-- Filters Sidebar -->
        <div class="w-full md:w-72 flex-shrink-0 card p-5 space-y-5 sticky top-6">
            <h3 class="font-semibold text-white/90 uppercase tracking-wider text-xs flex items-center gap-2">
                <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 7.086V4z"/></svg>
                Filters
            </h3>
            <form action="{{ route('messages.index') }}" method="GET" class="space-y-4">
                
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Search Keywords</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-input text-sm" placeholder="Subject or body...">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Mailbox</label>
                    <select name="mailbox_id" class="form-select text-sm">
                        <option value="">All Mailboxes</option>
                        @foreach($mailboxes as $mb)
                            <option value="{{ $mb->id }}" {{ ($filters['mailbox_id'] ?? '') == $mb->id ? 'selected' : '' }}>
                                {{ $mb->mailbox_key }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Sender</label>
                    <input type="text" name="sender" value="{{ $filters['sender'] ?? '' }}" class="form-input text-sm" placeholder="Name or email...">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Subject</label>
                    <input type="text" name="subject" value="{{ $filters['subject'] ?? '' }}" class="form-input text-sm" placeholder="Contains...">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">From Date</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-input text-sm px-2">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">To Date</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-input text-sm px-2">
                    </div>
                </div>

                @if($tags->count() > 0)
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Tag</label>
                    <select name="tag_id" class="form-select text-sm">
                        <option value="">All Tags</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" {{ ($filters['tag_id'] ?? '') == $tag->id ? 'selected' : '' }}>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="has_attachment" value="1" {{ !empty($filters['has_attachment']) ? 'checked' : '' }} class="form-checkbox text-brand-500 rounded bg-surface-800 border-surface-600 focus:ring-brand-500/50">
                        <span class="text-sm text-slate-400 group-hover:text-slate-300 transition">Has Attachment</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-surface-700/60 flex items-center gap-3">
                    <button type="submit" class="btn-primary w-full justify-center">Apply</button>
                    @if(array_filter($filters))
                        <a href="{{ route('messages.index') }}" class="btn-secondary px-3" title="Clear Filters">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Main Content -->
        <div class="flex-1 min-w-0">
            <div class="card overflow-hidden">
                @if($messages->isEmpty())
                    <div class="text-center py-16">
                        <div class="w-16 h-16 rounded-2xl bg-surface-700 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-slate-400 font-medium">No messages found</p>
                        @if(array_filter($filters))
                            <p class="text-slate-600 text-sm mt-1">Try adjusting your filters</p>
                        @endif
                    </div>
                @else
                    <div class="divide-y divide-surface-700/60">
                        @foreach($messages as $msg)
                        @php
                            $initials = strtoupper(substr($msg->from_name ?: $msg->from_email, 0, 2));
                        @endphp
                        <a href="{{ route('messages.show', $msg->id) }}"
                           class="flex items-center gap-4 px-5 py-4 hover:bg-surface-750/50 transition group {{ !$msg->is_read ? 'border-l-2 border-l-brand-500 bg-brand-600/5' : '' }}">

                            <div class="avatar-initials flex-shrink-0 text-xs">{{ $initials }}</div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline justify-between gap-4">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-sm {{ !$msg->is_read ? 'font-semibold text-white' : 'font-medium text-slate-300' }} truncate">
                                            {{ $msg->from_name ?: $msg->from_email }}
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-surface-700 text-slate-400 border border-surface-600 flex-shrink-0">
                                            {{ $msg->mailbox->mailbox_key }}
                                        </span>
                                    </div>
                                    <span class="text-xs text-slate-500 flex-shrink-0">
                                        {{ $msg->received_at->format('M j, g:i A') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-sm {{ !$msg->is_read ? 'text-slate-200' : 'text-slate-400' }} truncate">
                                        {{ $msg->subject ?: '(No Subject)' }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center gap-3 mt-1.5">
                                    <!-- Tags -->
                                    @if($msg->tags->count() > 0)
                                        <div class="flex items-center gap-1.5">
                                            @foreach($msg->tags as $tag)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-medium" style="{{ $tag->color ? 'background-color:'.$tag->color.'20; color:'.$tag->color.'; border: 1px solid '.$tag->color.'40;' : 'background-color: rgb(71 85 105 / 0.2); color: #94a3b8; border: 1px solid rgb(71 85 105 / 0.4);' }}">
                                                    {{ $tag->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- Attachment Indicator -->
                                    @if($msg->attachments->count() > 0)
                                        <span class="flex items-center gap-1 text-[11px] text-slate-500">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            {{ $msg->attachments->count() }}
                                        </span>
                                    @endif
                                    
                                    @if($msg->snippet)
                                    <span class="text-xs text-slate-600 truncate hidden md:block flex-1">
                                        — {{ Str::limit($msg->snippet, 60) }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            @if(!$msg->is_read)
                            <div class="w-2 h-2 rounded-full bg-brand-500 flex-shrink-0"></div>
                            @endif
                        </a>
                        @endforeach
                    </div>

                    @if($messages->hasPages())
                    <div class="px-5 py-4 border-t border-surface-700">
                        {{ $messages->appends($filters)->links() }}
                    </div>
                    @endif
                @endif
            </div>
        </div>

    </div>
</x-app-layout>
