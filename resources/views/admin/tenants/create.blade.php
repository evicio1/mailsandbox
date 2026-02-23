<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Tenant</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.tenants.store') }}">
                    @csrf

                    <div class="mb-4">
                        <x-input-label for="name" value="Tenant / Company Name" />
                        <x-text-input id="name" name="name" type="text"
                            class="block mt-1 w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="plan" value="Plan" />
                        <select id="plan" name="plan"
                            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach(['free','starter','pro','enterprise'] as $plan)
                                <option value="{{ $plan }}" @selected(old('plan') === $plan)>{{ ucfirst($plan) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('plan')" class="mt-2" />
                    </div>

                    <hr class="my-5">
                    <p class="text-sm text-gray-600 mb-3 font-medium">Tenant Owner (Admin)</p>

                    <div class="mb-4">
                        <x-input-label for="owner_name" value="Owner Name" />
                        <x-text-input id="owner_name" name="owner_name" type="text"
                            class="block mt-1 w-full" :value="old('owner_name')" required />
                        <x-input-error :messages="$errors->get('owner_name')" class="mt-2" />
                    </div>

                    <div class="mb-6">
                        <x-input-label for="owner_email" value="Owner Email" />
                        <x-text-input id="owner_email" name="owner_email" type="email"
                            class="block mt-1 w-full" :value="old('owner_email')" required />
                        <x-input-error :messages="$errors->get('owner_email')" class="mt-2" />
                        <p class="text-xs text-gray-400 mt-1">A welcome email with a password setup link will be sent to this address.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>Create Tenant</x-primary-button>
                        <a href="{{ route('admin.tenants.index') }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
