<x-guest-layout>
    <div class="mb-6">
        <div class="mb-4 border-b border-gray-200 pb-3">
            <span class="text-xs text-gray-500 uppercase tracking-wide">Account</span>
            <div class="font-medium text-gray-800">
                {{ session('mfa_pending_user_id') 
                    ? \App\Models\User::find(session('mfa_pending_user_id'))->email 
                    : auth()->user()->email }}
            </div>
        </div>
        <p class="text-gray-800 font-semibold text-base mb-1">Two-Factor Verification</p>
        <p class="text-sm text-gray-600">Enter the 6-digit code from your authenticator app, or use a recovery code.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('two-factor.challenge.verify') }}" id="mfa-form">
        @csrf

        {{-- TOTP code --}}
        <div id="totp-section">
            <x-input-label for="code" value="Authenticator Code" />
            <x-text-input
                id="code" name="code" type="text"
                inputmode="numeric" autocomplete="one-time-code"
                class="block mt-1 w-full text-center tracking-[0.5em] text-xl"
                maxlength="6" autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        {{-- Recovery code (hidden by default) --}}
        <div id="recovery-section" class="hidden mt-4">
            <x-input-label for="recovery_code" value="Recovery Code" />
            <x-text-input
                id="recovery_code" name="recovery_code" type="text"
                autocomplete="off"
                placeholder="XXXXX-XXXXX"
                class="block mt-1 w-full font-mono tracking-wider" />
            <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center mt-6">
            Verify
        </x-primary-button>

        <button type="button" id="toggle-recovery"
            class="mt-4 w-full text-center text-sm text-indigo-600 hover:underline">
            Use a recovery code instead
        </button>
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
            // clear values when switching
            document.getElementById('code').value = '';
            document.getElementById('recovery_code').value = '';
        });
    </script>

    <div class="mt-6 text-center border-t border-gray-100 pt-4">
        <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-gray-800 underline">
            Cancel &amp; Log Out
        </a>
    </div>
</x-guest-layout>
