<x-guest-layout>
    <div class="w-full max-w-md">
        <div class="mb-6 text-sm text-slate-400">
            @if(session('warning'))
                <div class="p-3 bg-amber-900/20 border border-amber-700/50 rounded-lg text-amber-400 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    {{ session('warning') }}
                </div>
            @endif

            <div class="mb-4 border-b border-surface-700/50 pb-3 flex flex-col">
                <span class="text-xs text-slate-500 uppercase tracking-wide">Account</span>
                <span class="font-medium text-white">{{ auth()->user()->email }}</span>
            </div>

            <h2 class="text-2xl font-bold text-white mb-2">Set Up Two-Factor Auth</h2>
            <p>Scan the QR code below with your authenticator app (Google Authenticator, Authy, etc.), then enter the 6-digit code to confirm setup.</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        {{-- QR Code --}}
        <div class="flex justify-center mb-6">
            <div class="p-3 bg-white border border-surface-600 rounded-xl inline-block shadow-lg">
                {!! $qrCodeSvg !!}
            </div>
        </div>

        {{-- Manual secret --}}
        <div class="mb-6">
            <p class="text-xs text-slate-500 text-center mb-2">Can't scan? Enter this key manually:</p>
            <code class="block text-center text-sm font-mono bg-surface-800 text-brand-300 rounded-lg px-3 py-3 break-all tracking-widest border border-surface-700 shadow-inner">
                {{ $secret }}
            </code>
        </div>

        {{-- Confirm form --}}
        <form method="POST" action="{{ route('two-factor.enroll.confirm') }}" class="space-y-5">
            @csrf
            <div>
                <x-input-label for="code" value="Verification Code" />
                <x-text-input
                    id="code" name="code" type="text"
                    inputmode="numeric" autocomplete="one-time-code"
                    class="block mt-1 w-full text-center tracking-[0.5em] text-xl font-mono"
                    maxlength="6" required autofocus />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <div class="pt-2">
                <x-primary-button class="w-full justify-center">
                    Confirm &amp; Enable 2FA
                </x-primary-button>
            </div>
        </form>

        <div class="mt-8 text-center pt-5 border-t border-surface-700/50">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-slate-500 hover:text-white transition">
                    Log Out
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
