<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h1 class="page-title">Tenants</h1>
                <p class="page-subtitle">Manage all tenant organisations</p>
            </div>
            <a href="{{ route('admin.tenants.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Tenant
            </a>
        </div>
    </x-slot>

    <div class="space-y-5 animate-fade-in">

        @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-emerald-900/30 border border-emerald-700/50 text-emerald-400 rounded-xl text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        <!-- Filters -->
        <form method="GET" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search by name…"
                   class="form-input flex-1">
            <select name="status"
                    class="form-input w-48">
                <option value="">All Statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
            </select>
            <button type="submit" class="btn-secondary">Filter</button>
        </form>

        <!-- Table -->
        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Owner</th>
                        <th>Members</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                    <tr>
                        <td>
                            <a href="{{ route('admin.tenants.show', $tenant) }}"
                               class="font-semibold text-brand-400 hover:text-brand-300 transition">
                                {{ $tenant->name }}
                            </a>
                            <div class="text-xs text-slate-600 mt-0.5">{{ $tenant->slug }}</div>
                        </td>
                        <td>
                            <span class="badge-gray capitalize">{{ $tenant->plan->name ?? 'Free' }}</span>
                            @if($tenant->subscribed('default'))
                                @if($tenant->subscription('default')->pastDue())
                                    <span class="badge-red ml-1 text-[10px]">Past Due</span>
                                @elseif($tenant->subscription('default')->canceled())
                                    <span class="badge-amber ml-1 text-[10px]">Canceled</span>
                                @endif
                            @endif
                        </td>
                        <td>
                            @if($tenant->status === 'active')
                                <span class="badge-green">Active</span>
                            @else
                                <span class="badge-red">{{ ucfirst($tenant->status) }}</span>
                            @endif
                        </td>
                        <td class="text-slate-400">{{ $tenant->owner?->email ?? '—' }}</td>
                        <td class="text-slate-400">{{ $tenant->users_count }}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.tenants.edit', $tenant) }}"
                                   class="text-xs text-brand-400 hover:text-brand-300 font-medium transition">
                                    Edit
                                </a>
                                @if($tenant->isActive())
                                <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}">
                                    @csrf
                                    <button class="text-xs text-red-400 hover:text-red-300 font-medium transition">Suspend</button>
                                </form>
                                @else
                                <form method="POST" action="{{ route('admin.tenants.activate', $tenant) }}">
                                    @csrf
                                    <button class="text-xs text-emerald-400 hover:text-emerald-300 font-medium transition">Activate</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 text-slate-500">No tenants found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($tenants->hasPages())
            <div class="px-4 py-4 border-t border-surface-700">
                {{ $tenants->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
