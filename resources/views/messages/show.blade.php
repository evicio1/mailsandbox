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

    <div class="max-w-4xl space-y-5 animate-fade-in">

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

        <!-- Message Headers -->
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
            <div class="flex items-center gap-1 mb-0 justify-between">
                <div class="flex items-center gap-1">
                    @if(!empty($message->html_body_sanitized))
                    <button @click="tab = 'html'"
                            :class="tab === 'html' ? 'bg-surface-700 text-white border-surface-600' : 'text-slate-500 hover:text-slate-300 border-transparent'"
                            class="px-4 py-2 text-sm font-medium rounded-t-lg border transition">
                        HTML View
                    </button>
                    @endif
                    <button @click="tab = 'text'"
                            :class="tab === 'text' ? 'bg-surface-700 text-white border-surface-600' : 'text-slate-500 hover:text-slate-300 border-transparent'"
                            class="px-4 py-2 text-sm font-medium rounded-t-lg border transition">
                        Plain Text
                    </button>
                </div>
                
                @if(!empty($message->raw_file_path))
                <a href="{{ route('messages.raw', $message->id) }}" target="_blank"
                   class="px-4 py-2 text-sm font-medium rounded-t-lg border border-transparent text-brand-400 hover:text-brand-300 transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    Original (.eml)
                </a>
                @endif
            </div>

            <div class="card overflow-hidden min-h-[300px]" :class="{ 'rounded-tl-none': tab === 'html' || tab === 'text' }">
                @if(!empty($message->html_body_sanitized))
                <div x-show="tab === 'html'" class="p-6">
                    <!-- Render the email HTML in an isolated iframe-like container -->
                    <div class="prose prose-invert max-w-none prose-a:text-brand-400">
                        {!! $htmlBody !!}
                    </div>
                </div>
                @endif
                <div x-show="tab === 'text'"
                     style="display: {{ empty($message->html_body_sanitized) ? 'block' : 'none' }};"
                     class="p-6 whitespace-pre-wrap font-mono text-sm text-slate-300 overflow-x-auto leading-relaxed">
                    {{ $message->text_body ?: 'No text body available.' }}
                </div>
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
