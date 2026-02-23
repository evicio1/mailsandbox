<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inbox Directory') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex gap-4">
                <form action="{{ route('dashboard') }}" method="GET" class="flex w-full gap-4">
                    <input type="text" name="q" value="{{ $search }}" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Search for mailbox e.g., 'qa+login'...">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">Search</button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4 p-6">
                @if($mailboxes->isEmpty())
                    <p class="text-gray-500">No mailboxes found.</p>
                @else
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($mailboxes as $mb)
                            <a href="{{ route('mailboxes.show', $mb->id) }}" class="flex justify-between items-center p-4 border rounded-lg hover:bg-gray-50 transition duration-150 ease-in-out">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ $mb->mailbox_key }}</h3>
                                    <div class="text-sm text-gray-500">Created: {{ $mb->created_at->format('Y-m-d H:i') }}</div>
                                </div>
                                <div>
                                    <span class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">View Inbox</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    
                    <div class="mt-4">
                        {{ $mailboxes->appends(['q' => $search])->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
