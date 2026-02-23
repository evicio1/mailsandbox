<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Recovery Codes</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if(session('success'))
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
                    <strong>Store these codes safely.</strong> Each code can only be used once.
                    If you lose access to your authenticator app, you can use one of these to log in.
                </div>

                @php $codes = session('recovery_codes') ?: auth()->user()->getRecoveryCodes(); @endphp

                <div class="grid grid-cols-2 gap-2 mb-6">
                    @foreach($codes as $code)
                        <code class="block bg-gray-100 rounded px-3 py-2 font-mono text-sm text-center tracking-widest">{{ $code }}</code>
                    @endforeach
                </div>

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
