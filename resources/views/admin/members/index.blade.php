<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Members — {{ $tenant->name }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded text-sm">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($members as $member)
                        <tr>
                            <td class="px-6 py-4 font-medium text-gray-800">{{ $member->name }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $member->email }}</td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.members.update', [$tenant, $member]) }}"
                                      class="flex items-center gap-2">
                                    @csrf @method('PUT')
                                    <select name="role"
                                        class="border-gray-300 rounded-md text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @foreach($roles as $role)
                                            @if($role->name !== 'SuperAdmin')
                                            <option value="{{ $role->name }}"
                                                @selected($member->roles->pluck('name')->contains($role->name))>
                                                {{ $role->name }}
                                            </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <button class="text-xs text-indigo-600 hover:underline">Update</button>
                                </form>
                            </td>
                            <td class="px-6 py-4">
                                @unless($member->isSuperAdmin())
                                <form method="POST" action="{{ route('admin.members.destroy', [$tenant, $member]) }}"
                                      onsubmit="return confirm('Remove this member?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-600 hover:underline">Remove</button>
                                </form>
                                @endunless
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400">No members yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-6 py-4 border-t border-gray-100">{{ $members->links() }}</div>
            </div>

            <div class="mt-4">
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-sm text-gray-500 hover:underline">← Back to Tenant</a>
            </div>
        </div>
    </div>
</x-app-layout>
