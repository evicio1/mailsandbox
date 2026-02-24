<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white">Welcome back</h2>
        <p class="text-slate-400 mt-1 text-sm">Sign in to your account to continue</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input
                id="email"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
                placeholder="you@example.com"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <x-input-label for="password" :value="__('Password')" class="mb-0" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-xs text-brand-400 hover:text-brand-300 transition">
                        Forgot password?
                    </a>
                @endif
            </div>
            <x-text-input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center gap-2">
            <input id="remember_me"
                   type="checkbox"
                   name="remember"
                   class="w-4 h-4 rounded border-surface-600 bg-surface-700 text-brand-500 focus:ring-brand-500 focus:ring-offset-surface-900">
            <label for="remember_me" class="text-sm text-slate-400 cursor-pointer">
                {{ __('Keep me signed in') }}
            </label>
        </div>

        <x-primary-button class="w-full justify-center py-3 text-sm">
            {{ __('Sign in') }}
        </x-primary-button>
    </form>

    @if (Route::has('register'))
    <p class="mt-6 text-center text-sm text-slate-500">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-brand-400 hover:text-brand-300 font-medium transition">
            Create one free
        </a>
    </p>
    @endif
</x-guest-layout>
