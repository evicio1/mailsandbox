<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Tenant: {{ $tenant->name }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <x-input-label for="name" value="Tenant Name" />
                        <x-text-input id="name" name="name" type="text"
                            class="block mt-1 w-full" :value="old('name', $tenant->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="plan" value="Plan" />
                        <select id="plan" name="plan"
                            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach(['free','starter','pro','enterprise'] as $plan)
                                <option value="{{ $plan }}" @selected(old('plan', $tenant->plan) === $plan)>{{ ucfirst($plan) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('plan')" class="mt-2" />
                    </div>

                    <div class="mb-6">
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status"
                            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="active"    @selected(old('status', $tenant->status) === 'active')>Active</option>
                            <option value="suspended" @selected(old('status', $tenant->status) === 'suspended')>Suspended</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div class="flex gap-3">
                        <x-primary-button>Save Changes</x-primary-button>
                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-sm text-gray-500 hover:underline my-auto">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
