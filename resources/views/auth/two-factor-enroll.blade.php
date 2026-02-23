<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        @if(session('warning'))
            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded text-yellow-800 mb-4">
                {{ session('warning') }}
            </div>
        @endif
        <div class="mb-4 border-b border-gray-200 pb-3">
            <span class="text-xs text-gray-500 uppercase tracking-wide">Account</span>
            <div class="font-medium text-gray-800">{{ auth()->user()->email }}</div>
        </div>
        <p class="font-semibold text-gray-800 text-base mb-1">Set Up Two-Factor Authentication</p>
        <p>Scan the QR code below with your authenticator app (Google Authenticator, Authy, etc.), then enter the 6-digit code to confirm setup.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- QR Code --}}
    <div class="flex justify-center mb-4">
        <div class="p-2 bg-white border border-gray-200 rounded inline-block">
            {!! $qrCodeSvg !!}
        </div>
    </div>

    {{-- Manual secret --}}
    <div class="mb-4">
        <p class="text-xs text-gray-500 text-center mb-1">Can't scan? Enter this key manually:</p>
        <code class="block text-center text-sm font-mono bg-gray-100 rounded px-3 py-2 break-all tracking-widest">
            {{ $secret }}
        </code>
    </div>

    {{-- Confirm form --}}
    <form method="POST" action="{{ route('two-factor.enroll.confirm') }}">
        @csrf

        <div class="mb-4">
            <x-input-label for="code" value="Verification Code" />
            <x-text-input
                id="code" name="code" type="text"
                inputmode="numeric" autocomplete="one-time-code"
                class="block mt-1 w-full text-center tracking-[0.5em] text-xl"
                maxlength="6" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">
            Confirm &amp; Enable 2FA
        </x-primary-button>
    </form>

    <div class="mt-6 text-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-800 underline">
                Log Out
            </button>
        </form>
    </div>
</x-guest-layout>
