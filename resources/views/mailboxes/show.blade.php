<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📬 {{ $mailbox->mailbox_key }}
            </h2>
            <div class="text-sm text-gray-500 mt-1">Total Emails: {{ $messages->total() }}</div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-900">&larr; Back to Directory</a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                @if($messages->isEmpty())
                    <div class="p-6 text-gray-500">No messages in this mailbox yet.</div>
                @else
                    <div class="divide-y divide-gray-200">
                        @foreach($messages as $msg)
                            <a href="{{ route('messages.show', $msg->id) }}" class="block p-4 sm:p-6 hover:bg-gray-50 {{ !$msg->is_read ? 'bg-indigo-50 font-semibold' : '' }} transition duration-150">
                                <div class="grid grid-cols-1 md:grid-cols-[200px_1fr_150px] gap-2 md:gap-4 items-center">
                                    <div class="truncate text-gray-900">
                                        {{ $msg->from_name ?: $msg->from_email }}
                                    </div>
                                    <div class="truncate">
                                        <span class="text-gray-900">{{ $msg->subject ?: '(No Subject)' }}</span>
                                        <span class="text-gray-500 font-normal"> - {{ Str::limit($msg->snippet, 60) }}</span>
                                    </div>
                                    <div class="text-sm text-gray-500 md:text-right">
                                        {{ $msg->received_at->format('M j, Y g:i A') }}
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    
                    @if($messages->hasPages())
                        <div class="p-4 border-t border-gray-200">
                            {{ $messages->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
