<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-slate-500 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="page-title">Edit: {{ $tenant->name }}</h1>
                <p class="page-subtitle">Update tenant settings</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl animate-fade-in">
        <div class="card p-6">
            <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="name" value="Tenant Name" />
                    <x-text-input id="name" name="name" type="text" :value="old('name', $tenant->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="plan" value="Plan" />
                    <select id="plan" name="plan" class="form-input">
                        @foreach($plans as $planModel)
                            <option value="{{ $planModel->plan_id }}" @selected(old('plan', $tenant->current_plan_id) === $planModel->plan_id)>{{ $planModel->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('plan')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="inbox_limit_override" value="Custom Inbox Limit Override" />
                    <x-text-input id="inbox_limit_override" name="inbox_limit_override" type="number" min="-1" step="1" :value="old('inbox_limit_override', $tenant->inbox_limit_override)" placeholder="Leave blank for plan default. -1 for unlimited." />
                    <x-input-error :messages="$errors->get('inbox_limit_override')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="form-input">
                        <option value="active"    @selected(old('status', $tenant->status) === 'active')>Active</option>
                        <option value="suspended" @selected(old('status', $tenant->status) === 'suspended')>Suspended</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button>Save Changes</x-primary-button>
                    <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
