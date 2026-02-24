<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.tenants.index') }}" class="text-slate-500 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="page-title">New Tenant</h1>
                <p class="page-subtitle">Create a new organisation and owner account</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl animate-fade-in">
        <div class="card p-6">
            <form method="POST" action="{{ route('admin.tenants.store') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="name" value="Tenant / Company Name" />
                    <x-text-input id="name" name="name" type="text" :value="old('name')" required placeholder="Acme Corp" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="plan" value="Plan" />
                    <select id="plan" name="plan" class="form-input">
                        @foreach(['free','starter','pro','enterprise'] as $plan)
                            <option value="{{ $plan }}" @selected(old('plan') === $plan)>{{ ucfirst($plan) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('plan')" class="mt-2" />
                </div>

                <div class="border-t border-surface-700 pt-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-4">Tenant Owner (Admin)</p>

                    <div class="space-y-4">
                        <div>
                            <x-input-label for="owner_name" value="Owner Name" />
                            <x-text-input id="owner_name" name="owner_name" type="text" :value="old('owner_name')" required placeholder="Jane Smith" />
                            <x-input-error :messages="$errors->get('owner_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="owner_email" value="Owner Email" />
                            <x-text-input id="owner_email" name="owner_email" type="email" :value="old('owner_email')" required placeholder="owner@company.com" />
                            <x-input-error :messages="$errors->get('owner_email')" class="mt-2" />
                            <p class="text-xs text-slate-600 mt-1.5">A welcome email with a password setup link will be sent.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button>Create Tenant</x-primary-button>
                    <a href="{{ route('admin.tenants.index') }}" class="btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
