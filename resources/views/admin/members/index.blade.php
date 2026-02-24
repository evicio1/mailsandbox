<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-slate-500 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="page-title">Members</h1>
                    <p class="page-subtitle">{{ $tenant->name }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl animate-fade-in space-y-4">

        @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-emerald-900/30 border border-emerald-700/50 text-emerald-400 rounded-xl text-sm">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="flex items-center gap-3 p-4 bg-red-900/30 border border-red-700/50 text-red-400 rounded-xl text-sm">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $errors->first() }}
        </div>
        @endif

        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="avatar-initials text-xs">{{ strtoupper(substr($member->name, 0, 2)) }}</div>
                                <span class="font-medium text-white">{{ $member->name }}</span>
                            </div>
                        </td>
                        <td class="text-slate-400">{{ $member->email }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.members.update', [$tenant, $member]) }}"
                                  class="flex items-center gap-2">
                                @csrf @method('PUT')
                                <select name="role" class="form-input py-1.5 text-xs w-40">
                                    @foreach($roles as $role)
                                        @if($role->name !== 'SuperAdmin')
                                        <option value="{{ $role->name }}"
                                            @selected($member->roles->pluck('name')->contains($role->name))>
                                            {{ $role->name }}
                                        </option>
                                        @endif
                                    @endforeach
                                </select>
                                <button class="text-xs text-brand-400 hover:text-brand-300 font-medium transition">Update</button>
                            </form>
                        </td>
                        <td>
                            @unless($member->isSuperAdmin())
                            <form method="POST" action="{{ route('admin.members.destroy', [$tenant, $member]) }}"
                                  onsubmit="return confirm('Remove this member from the tenant?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-400 hover:text-red-300 font-medium transition">Remove</button>
                            </form>
                            @endunless
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-12 text-slate-500">No members yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($members->hasPages())
            <div class="px-4 py-4 border-t border-surface-700">
                {{ $members->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
