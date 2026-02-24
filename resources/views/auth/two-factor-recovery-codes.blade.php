<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Recovery Codes</h1>
        <p class="page-subtitle">Save these codes to access your account if you lose your authenticator.</p>
    </x-slot>

    <div class="max-w-xl animate-fade-in">
        <div class="card p-6 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-emerald-900/30 border border-emerald-700/50 text-emerald-400 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <div class="p-4 bg-amber-900/20 border border-amber-700/40 rounded-xl text-sm text-amber-500/90 leading-relaxed">
                <strong class="text-amber-400 block mb-1">Store these codes safely.</strong> 
                Each code can only be used once. If you lose access to your authenticator app, you can use one of these to log in.
            </div>

            @php $codes = session('recovery_codes') ?: auth()->user()->getRecoveryCodes(); @endphp

            <div class="grid grid-cols-2 gap-3">
                @foreach($codes as $code)
                    <code class="block bg-surface-800 border border-surface-700 rounded-lg px-3 py-3 font-mono text-sm text-center tracking-widest text-brand-300 shadow-inner">
                        {{ $code }}
                    </code>
                @endforeach
            </div>

            <div class="border-t border-surface-700/50 pt-6 mt-6">
                {{-- Regenerate --}}
                <form method="POST" action="{{ route('two-factor.recovery-codes.regenerate') }}"
                      onsubmit="return confirm('This will invalidate all existing codes. Continue?')">
                    @csrf

                    <div class="mb-4">
                        <x-input-label for="password" value="Confirm Password to Regenerate" />
                        <x-text-input id="password" name="password" type="password"
                            class="block mt-1 w-full" autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <x-secondary-button type="submit">
                        Regenerate Recovery Codes
                    </x-secondary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
