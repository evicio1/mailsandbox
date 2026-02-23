<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tenants</h2>
            <a href="{{ route('admin.tenants.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow-sm hover:bg-indigo-700">
                + New Tenant
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filters --}}
            <form method="GET" class="mb-4 flex gap-3">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search by name…"
                    class="border-gray-300 rounded-md shadow-sm text-sm flex-1 focus:ring-indigo-500 focus:border-indigo-500">
                <select name="status" class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                </select>
                <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">Filter</button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Members</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($tenants as $tenant)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.tenants.show', $tenant) }}" class="font-medium text-indigo-600 hover:underline">
                                    {{ $tenant->name }}
                                </a>
                                <div class="text-xs text-gray-400">{{ $tenant->slug }}</div>
                            </td>
                            <td class="px-6 py-4 capitalize">{{ $tenant->plan }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $tenant->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($tenant->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $tenant->owner?->email ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $tenant->users_count }}</td>
                            <td class="px-6 py-4 flex gap-2">
                                <a href="{{ route('admin.tenants.edit', $tenant) }}"
                                   class="text-indigo-600 hover:underline text-xs">Edit</a>
                                @if($tenant->isActive())
                                    <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}">
                                        @csrf
                                        <button class="text-red-600 hover:underline text-xs">Suspend</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.tenants.activate', $tenant) }}">
                                        @csrf
                                        <button class="text-green-600 hover:underline text-xs">Activate</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400">No tenants found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $tenants->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
