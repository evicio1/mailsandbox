<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Active Sessions</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @forelse($sessions as $session)
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 last:border-0">
                    <div>
                        <div class="text-sm font-medium text-gray-800">
                            {{ $session->ip_address }}
                            @if($session->is_current)
                                <span class="ml-2 text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">Current</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5">
                            Last active {{ $session->last_active->diffForHumans() }}
                            @if($session->user_agent)
                                · {{ Str::limit($session->user_agent, 60) }}
                            @endif
                        </div>
                    </div>
                    @unless($session->is_current)
                    <form method="POST" action="{{ route('sessions.destroy', $session->id) }}"
                          onsubmit="return confirm('Revoke this session?')">
                        @csrf @method('DELETE')
                        <button class="text-sm text-red-600 hover:underline">Revoke</button>
                    </form>
                    @endunless
                </div>
                @empty
                <div class="px-6 py-8 text-center text-gray-400 text-sm">No active sessions found.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
