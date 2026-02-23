<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate">
            {{ $message->subject ?: '(No Subject)' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('mailboxes.show', $message->mailbox_id) }}" class="text-indigo-600 hover:text-indigo-900">&larr; Back to {{ $message->mailbox->mailbox_key }}</a>
            </div>

            <!-- Message Headers -->
            <div class="bg-white px-6 py-6 shadow-sm border border-gray-200 rounded-lg mb-6">
                <div class="grid grid-cols-[80px_1fr] md:grid-cols-[100px_1fr] gap-y-2 gap-x-4 text-sm md:text-base">
                    <div class="text-right font-medium text-gray-500">From:</div>
                    <div class="text-gray-900 break-words">
                        <strong>{{ $message->from_name }}</strong> &lt;{{ $message->from_email }}&gt;
                    </div>

                    <div class="text-right font-medium text-gray-500">To:</div>
                    <div class="text-gray-900 break-words">{{ implode(', ', $message->to_raw ?? []) }}</div>

                    @if(!empty($message->cc_raw))
                        <div class="text-right font-medium text-gray-500">CC:</div>
                        <div class="text-gray-900 break-words">{{ implode(', ', $message->cc_raw) }}</div>
                    @endif

                    <div class="text-right font-medium text-gray-500">Date:</div>
                    <div class="text-gray-900">{{ $message->received_at->format('D, M j, Y \a\t g:i A') }}</div>
                </div>
            </div>

            <!-- OTP Extraction -->
            @if($extractedOtp)
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 md:p-6 flex items-center justify-between mb-6 shadow-sm">
                    <div>
                        <div class="text-xs text-green-700 uppercase tracking-wider font-semibold mb-1">Detected OTP / Code</div>
                        <div id="otpText" class="text-2xl font-mono tracking-widest text-green-600 font-bold">{{ $extractedOtp }}</div>
                    </div>
                    <button onclick="copyOtp(this)" class="px-4 py-2 border border-green-600 text-green-600 rounded-md hover:bg-green-600 hover:text-white transition text-sm font-medium">Copy Code</button>
                </div>
            @endif

            <!-- Attachments -->
            @php $attachments = $message->attachments ?? []; @endphp
            @if(count($attachments) > 0)
                <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6 shadow-sm">
                    <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                        Attachments ({{ count($attachments) }})
                    </h4>
                    <div class="grid gap-2">
                        @foreach($attachments as $att)
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-3 bg-gray-50 border border-gray-200 rounded-md gap-2">
                                <div class="truncate w-full sm:w-auto">
                                    <span class="font-medium text-gray-900">{{ $att->filename }}</span>
                                    <span class="text-xs text-gray-500 ml-2">({{ \App\Services\MailboxService::formatBytes($att->size_bytes) }})</span>
                                </div>
                                <a href="{{ route('attachments.download', $att->id) }}" target="_blank" class="px-3 py-1 bg-white border border-gray-300 text-gray-700 rounded text-sm hover:bg-gray-50 shrink-0">Download</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Body Tabs -->
            <div x-data="{ tab: '{{ !empty($message->html_body_sanitized) ? 'html' : 'text' }}' }" class="mt-8">
                <div class="flex space-x-1 border-b border-gray-200">
                    @if(!empty($message->html_body_sanitized))
                        <button @click="tab = 'html'" :class="{ 'bg-white text-indigo-600 border-t border-l border-r border-gray-200': tab === 'html', 'bg-gray-50 text-gray-500 hover:text-gray-700': tab !== 'html' }" class="px-6 py-3 text-sm font-medium rounded-t-lg focus:outline-none transition">
                            HTML View
                        </button>
                    @endif
                    <button @click="tab = 'text'" :class="{ 'bg-white text-indigo-600 border-t border-l border-r border-gray-200': tab === 'text', 'bg-gray-50 text-gray-500 hover:text-gray-700': tab !== 'text' }" class="px-6 py-3 text-sm font-medium rounded-t-lg focus:outline-none transition">
                        Text View
                    </button>
                </div>

                <div class="bg-white border-b border-l border-r border-gray-200 rounded-b-lg p-6 min-h-[300px] shadow-sm">
                    @if(!empty($message->html_body_sanitized))
                        <div x-show="tab === 'html'" class="prose max-w-none">
                            {!! $message->html_body_sanitized !!}
                        </div>
                    @endif
                    <div x-show="tab === 'text'" class="whitespace-pre-wrap font-mono text-sm text-gray-800 overflow-x-auto" style="display: {{ empty($message->html_body_sanitized) ? 'block' : 'none' }};">
                        {{ $message->text_body ?: 'No text body available.' }}
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function copyOtp(btn) {
            const text = document.getElementById('otpText').innerText;
            navigator.clipboard.writeText(text).then(() => {
                const originalText = btn.innerText;
                btn.innerText = 'Copied!';
                btn.classList.add('bg-green-600', 'text-white');
                btn.classList.remove('text-green-600');
                
                setTimeout(() => {
                    btn.innerText = originalText;
                    btn.classList.remove('bg-green-600', 'text-white');
                    btn.classList.add('text-green-600');
                }, 2000);
            });
        }
    </script>
    @endpush
</x-app-layout>
