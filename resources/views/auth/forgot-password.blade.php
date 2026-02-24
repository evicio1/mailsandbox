<x-guest-layout>
    <div class="w-full max-w-md">
        <h2 class="text-2xl font-bold text-white mb-2">Forgot Password</h2>
        <p class="text-sm text-slate-400 mb-8">
            {{ __('No problem. Just let us know your email address and we will email you a password reset link.') }}
        </p>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="pt-2 flex items-center justify-between">
                <a class="text-sm text-brand-400 hover:text-brand-300 font-medium transition" href="{{ route('login') }}">
                    {{ __('Back to login') }}
                </a>
                <x-primary-button>
                    {{ __('Send Reset Link') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
