<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('mailboxes.show', $message->mailbox_id) }}"
               class="text-slate-500 hover:text-white transition flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="page-title truncate">{{ $message->subject ?: '(No Subject)' }}</h1>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-fade-in items-start">
        <div class="col-span-1 md:col-span-2 space-y-5">

        <!-- OTP Banner -->
        @if($extractedOtp)
        <div class="otp-banner">
            <div>
                <div class="text-xs text-emerald-400 uppercase tracking-widest font-semibold mb-1 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse-slow inline-block"></span>
                    Detected OTP / Code
                </div>
                <div id="otpText" class="text-3xl font-mono tracking-[0.3em] text-emerald-300 font-bold">{{ $extractedOtp }}</div>
            </div>
            <button onclick="copyOtp(this)"
                    class="btn-secondary border-emerald-700/60 text-emerald-400 hover:bg-emerald-900/40 hover:text-emerald-300 hover:border-emerald-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                </svg>
                Copy
            </button>
        </div>
        @endif

        <!-- Message Headers & Tags -->
        <div class="card p-6">
            <div class="flex items-start gap-4">
                <div class="avatar-initials text-sm flex-shrink-0">
                    {{ strtoupper(substr($message->from_name ?: $message->from_email, 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="font-semibold text-white">{{ $message->from_name ?: $message->from_email }}</div>
                            @if($message->from_name)
                            <div class="text-sm text-slate-500">{{ $message->from_email }}</div>
                            @endif
                        </div>
                        <div class="text-sm text-slate-500 flex-shrink-0">
                            {{ $message->received_at->format('D, M j, Y \a\t g:i A') }}
                        </div>
                    </div>

                    <div class="mt-3 space-y-1.5 text-sm text-slate-400">
                        <div>
                            <span class="text-slate-600 inline-block w-6">To:</span>
                            <span class="text-slate-300">{{ implode(', ', $message->to_raw ?? []) }}</span>
                        </div>
                        @if(!empty($message->cc_raw))
                        <div>
                            <span class="text-slate-600 inline-block w-6">CC:</span>
                            <span class="text-slate-300">{{ implode(', ', $message->cc_raw) }}</span>
                        </div>
                        @endif
                    </div>
                    
                    <!-- Tags Area -->
                    <div class="mt-4 pt-4 border-t border-surface-700/60 flex flex-wrap items-center gap-2">
                        @foreach($message->tags as $tag)
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border border-surface-600/50"
                                 style="background-color: {{ $tag->color }}15; color: {{ $tag->color }}; border-color: {{ $tag->color }}30;">
                                <span>{{ $tag->name }}</span>
                                <form action="{{ route('messages.tags.toggle', $message->id) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="name" value="{{ $tag->name }}">
                                    <button type="submit" class="hover:text-red-400 transition" title="Remove Tag">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </form>
                            </div>
                        @endforeach

                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" @click.away="open = false" 
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-surface-700 text-slate-300 hover:text-white hover:bg-surface-600 transition border border-surface-600 border-dashed">
                                <svg class="w-3h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Tag
                            </button>

                            <div x-show="open" style="display: none;" 
                                 class="absolute top-full left-0 mt-2 w-64 bg-surface-800 rounded-xl shadow-xl border border-surface-600 p-4 z-20">
                                <form action="{{ route('messages.tags.toggle', $message->id) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-medium text-slate-400 mb-1">Tag Name</label>
                                        <input type="text" name="name" class="form-input text-sm py-1.5" placeholder="e.g. Action Required" required autofocus>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-400 mb-1">Color (optional)</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color" name="color" value="#8b5cf6" class="w-8 h-8 rounded cursor-pointer bg-surface-900 border-0 p-0">
                                            <span class="text-xs text-slate-500">Pick a color</span>
                                        </div>
                                    </div>
                                    <div class="pt-2">
                                        <button type="submit" class="btn-primary w-full py-1.5 text-xs">Save Tag</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attachments -->
        @php $attachments = $message->attachments ?? []; @endphp
        @if(count($attachments) > 0)
        <div class="card p-5">
            <h4 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                Attachments ({{ count($attachments) }})
            </h4>
            <div class="flex flex-wrap gap-2">
                @foreach($attachments as $att)
                <a href="{{ route('attachments.download', $att->id) }}"
                   target="_blank"
                   class="flex items-center gap-2 px-3 py-2 bg-surface-700 border border-surface-600 hover:border-brand-500/50 hover:bg-surface-700 rounded-lg text-sm text-slate-300 hover:text-white transition group">
                    <svg class="w-4 h-4 text-slate-500 group-hover:text-brand-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="truncate max-w-[180px]">{{ $att->filename }}</span>
                    <span class="text-xs text-slate-600 flex-shrink-0">{{ \App\Services\MailboxService::formatBytes($att->size_bytes) }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @php 
            $htmlBody = $message->html_body_sanitized;
            if (!empty($htmlBody) && count($attachments) > 0) {
                foreach ($attachments as $att) {
                    if ($att->content_id) {
                        $htmlBody = str_replace('cid:' . $att->content_id, route('attachments.download', $att->id), $htmlBody);
                    }
                }
            }
            $activeTab = !empty($message->html_body_sanitized) ? 'html' : 'text';
        @endphp
        
        <div x-data="{ tab: '{{ $activeTab }}' }">
            <!-- Tab Bar -->
            <div class="flex items-center overflow-x-auto gap-1 mb-0 border-b border-surface-700/60 scrollbar-hide">
                @if(!empty($message->html_body_sanitized))
                <button @click="tab = 'html'"
                        :class="tab === 'html' ? 'border-brand-500 text-white bg-surface-800' : 'border-transparent text-slate-400 hover:text-slate-300 hover:bg-surface-800/50'"
                        class="px-5 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                    HTML View
                </button>
                @endif
                <button @click="tab = 'text'"
                        :class="tab === 'text' ? 'border-brand-500 text-white bg-surface-800' : 'border-transparent text-slate-400 hover:text-slate-300 hover:bg-surface-800/50'"
                        class="px-5 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                    Plain Text
                </button>
                <button @click="tab = 'headers'"
                        :class="tab === 'headers' ? 'border-brand-500 text-white bg-surface-800' : 'border-transparent text-slate-400 hover:text-slate-300 hover:bg-surface-800/50'"
                        class="px-5 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                    Headers
                </button>
                <button @click="tab = 'raw'"
                        :class="tab === 'raw' ? 'border-brand-500 text-white bg-surface-800' : 'border-transparent text-slate-400 hover:text-slate-300 hover:bg-surface-800/50'"
                        class="px-5 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                    Raw Source
                </button>
            </div>

            <!-- Tab Content Area -->
            <div class="card overflow-hidden min-h-[400px] mt-4">
                
                <!-- HTML Tab -->
                @if(!empty($message->html_body_sanitized))
                <div x-show="tab === 'html'" class="p-6">
                    <div class="prose prose-invert max-w-none prose-a:text-brand-400">
                        {!! $htmlBody !!}
                    </div>
                </div>
                @endif
                
                <!-- Text Tab -->
                <div x-show="tab === 'text'"
                     style="display: {{ empty($message->html_body_sanitized) ? 'block' : 'none' }};"
                     class="p-6 whitespace-pre-wrap font-mono text-sm text-slate-300 overflow-x-auto leading-relaxed">
                    {{ $message->text_body ?: 'No text body available.' }}
                </div>

                <!-- Headers Tab -->
                <div x-show="tab === 'headers'" style="display: none;" class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <tbody class="divide-y divide-surface-700/60">
                                @php
                                    // Basic header parsing: Line by line, split by first colon
                                    $headersList = [];
                                    if ($message->headers_raw) {
                                        $lines = explode("\n", $message->headers_raw);
                                        $currentKey = '';
                                        foreach ($lines as $line) {
                                            if (preg_match('/^\s+(.*)/', $line, $matches) && $currentKey) {
                                                $headersList[$currentKey] .= ' ' . trim($matches[1]);
                                            } else {
                                                $parts = explode(':', $line, 2);
                                                if (count($parts) === 2) {
                                                    $currentKey = trim($parts[0]);
                                                    $headersList[$currentKey] = trim($parts[1]);
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                @forelse($headersList as $key => $values)
                                    <tr class="hover:bg-surface-800/30 transition">
                                        <th class="px-6 py-3 font-medium text-slate-400 whitespace-nowrap align-top w-48">{{ $key }}</th>
                                        <td class="px-6 py-3 text-slate-300 break-all font-mono text-xs">{{ $values }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-6 py-8 text-center text-slate-500">No headers available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Raw Source Tab -->
                <div x-show="tab === 'raw'" style="display: none;" class="p-0 relative h-full min-h-[400px]">
                    @if(!empty($message->raw_file_path))
                        <div class="absolute top-4 right-4 z-10">
                            <a href="{{ route('messages.raw', $message->id) }}" target="_blank"
                               class="btn-secondary px-3 py-1.5 text-xs flex items-center gap-1.5 bg-surface-800">
                               <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                               Download .eml
                            </a>
                        </div>
                        <iframe src="{{ route('messages.raw', $message->id) }}" class="w-full h-[600px] border-0 bg-surface-900"></iframe>
                    @else
                        <div class="flex items-center justify-center h-full text-slate-500 p-8 text-center min-h-[200px]">
                            Raw source file not available for this message.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        </div>

        <!-- Notes Sidebar -->
        <div class="col-span-1 border-l border-surface-700/60 pl-6 space-y-5 sticky top-6 self-start max-h-[calc(100vh-6rem)] flex flex-col pt-1">
            <h3 class="font-semibold text-white/90 uppercase tracking-wider text-xs flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    Team Notes ({{ $message->notes->count() }})
                </span>
            </h3>

            <!-- New Note Form -->
            <form action="{{ route('messages.notes.store', $message->id) }}" method="POST" class="space-y-3">
                @csrf
                <textarea name="content" rows="2" class="form-input text-sm resize-none placeholder-slate-500 bg-surface-800" placeholder="Write a note..." required></textarea>
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary py-1.5 px-3 text-xs">Add Note</button>
                </div>
            </form>

            <!-- Notes List -->
            <div class="flex-1 overflow-y-auto min-h-0 space-y-4 pr-2 custom-scrollbar">
                @forelse($message->notes()->latest()->get() as $note)
                    <div class="bg-surface-800/50 rounded-lg p-3 relative group">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0"
                                     style="background: linear-gradient(135deg, #4f46e5, #7c3aed)">
                                    {{ strtoupper(substr($note->user->name, 0, 2)) }}
                                </div>
                                <span class="text-xs font-semibold text-slate-300 truncate">{{ $note->user->name }}</span>
                            </div>
                            <span class="text-[10px] text-slate-500 whitespace-nowrap">{{ $note->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-xs text-slate-400 break-words leading-relaxed">
                            {{ $note->content }}
                        </p>
                        
                        @if(Auth::id() === $note->user_id || Auth::user()->isTenantAdmin())
                            <form action="{{ route('notes.destroy', $note->id) }}" method="POST" class="absolute top-2 right-2 hidden group-hover:block" onsubmit="return confirm('Delete this note?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-500 hover:text-red-400 bg-surface-800 rounded p-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-6 text-slate-500 text-xs">
                        No team notes yet. Be the first to comment!
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        function copyOtp(btn) {
            const text = document.getElementById('otpText').innerText.trim();
            navigator.clipboard.writeText(text).then(() => {
                const svg = btn.querySelector('svg');
                btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Copied!`;
                setTimeout(() => {
                    btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg> Copy`;
                }, 2000);
            });
        }
    </script>
    @endpush
</x-app-layout>
