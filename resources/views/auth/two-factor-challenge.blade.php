<x-guest-layout>
    <div class="w-full max-w-md">
        <div class="mb-6">
            <div class="mb-4 border-b border-surface-700/50 pb-3 flex flex-col">
                <span class="text-xs text-slate-500 uppercase tracking-wide">Account</span>
                <span class="font-medium text-white">
                    {{ session('mfa_pending_user_id') 
                        ? \App\Models\User::find(session('mfa_pending_user_id'))->email 
                        : auth()->user()->email }}
                </span>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">Two-Factor Verification</h2>
            <p class="text-sm text-slate-400">Enter the 6-digit code from your authenticator app, or use a recovery code.</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('two-factor.challenge.verify') }}" id="mfa-form" class="space-y-5">
            @csrf

            {{-- TOTP code --}}
            <div id="totp-section">
                <x-input-label for="code" value="Authenticator Code" />
                <x-text-input
                    id="code" name="code" type="text"
                    inputmode="numeric" autocomplete="one-time-code"
                    class="block mt-1 w-full text-center tracking-[0.5em] text-xl font-mono"
                    maxlength="6" autofocus />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            {{-- Recovery code (hidden by default) --}}
            <div id="recovery-section" class="hidden">
                <x-input-label for="recovery_code" value="Recovery Code" />
                <x-text-input
                    id="recovery_code" name="recovery_code" type="text"
                    autocomplete="off"
                    placeholder="XXXXX-XXXXX"
                    class="block mt-1 w-full font-mono tracking-wider text-center" />
                <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
            </div>

            <div class="pt-2">
                <x-primary-button class="w-full justify-center">
                    Verify
                </x-primary-button>
            </div>

            <div class="pt-2">
                <button type="button" id="toggle-recovery"
                    class="w-full text-center text-sm text-brand-400 hover:text-brand-300 transition">
                    Use a recovery code instead
                </button>
            </div>
        </form>

        <script>
            const toggle    = document.getElementById('toggle-recovery');
            const totp      = document.getElementById('totp-section');
            const recovery  = document.getElementById('recovery-section');
            let usingRecovery = false;

            toggle.addEventListener('click', () => {
                usingRecovery = !usingRecovery;
                totp.classList.toggle('hidden', usingRecovery);
                recovery.classList.toggle('hidden', !usingRecovery);
                toggle.textContent = usingRecovery
                    ? 'Use authenticator app instead'
                    : 'Use a recovery code instead';
                document.getElementById('code').value = '';
                document.getElementById('recovery_code').value = '';
            });
        </script>

        <div class="mt-6 text-center border-t border-surface-700/50 pt-4">
            <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:text-white transition">
                Cancel &amp; Log Out
            </a>
        </div>
    </div>
</x-guest-layout>
