<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('profile.edit') }}" class="text-slate-500 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="page-title">Active Sessions</h1>
                <p class="page-subtitle">Manage and revoke your active browser sessions</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl animate-fade-in space-y-5">
        @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-emerald-900/30 border border-emerald-700/50 text-emerald-400 rounded-xl text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        <div class="card overflow-hidden">
            @if($sessions->isEmpty())
                <div class="px-6 py-8 text-center text-slate-400 text-sm">No active sessions found.</div>
            @else
                <div class="divide-y divide-surface-700/60">
                    @foreach($sessions as $session)
                    <div class="flex items-start sm:items-center justify-between px-6 py-5 hover:bg-surface-750/30 transition">
                        <div class="flex items-start gap-4">
                            <div class="mt-1 sm:mt-0 w-10 h-10 rounded-xl bg-surface-700 flex items-center justify-center text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-white flex items-center gap-2">
                                    {{ $session->ip_address }}
                                    @if($session->is_current)
                                        <span class="badge-blue">This Device</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-400 mt-1 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                    <span>Last active {{ $session->last_active->diffForHumans() }}</span>
                                    @if($session->user_agent)
                                        <span class="hidden sm:inline text-slate-600">•</span>
                                        <span class="text-slate-500 truncate max-w-[200px] sm:max-w-md" title="{{ $session->user_agent }}">
                                            {{ Str::limit($session->user_agent, 60) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @unless($session->is_current)
                        <div class="ml-4 flex-shrink-0">
                            <form method="POST" action="{{ route('sessions.destroy', $session->id) }}" onsubmit="return confirm('Revoke this session?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-ghost text-red-400 hover:text-red-300 hover:bg-red-900/20 px-3 py-1.5 text-xs">
                                    Revoke
                                </button>
                            </form>
                        </div>
                        @endunless
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
