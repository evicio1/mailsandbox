<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.tenants.index') }}" class="text-slate-500 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="page-title">{{ $tenant->name }}</h1>
                    <p class="page-subtitle">{{ $tenant->slug }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.tenants.edit', $tenant) }}" class="btn-secondary text-xs">Edit</a>
                <form method="POST" action="{{ route('admin.tenants.resend-invite', $tenant) }}">
                    @csrf
                    <button class="btn-secondary text-xs text-brand-400 border-brand-600/40 hover:bg-brand-600/10">Resend Invite</button>
                </form>
                @if($tenant->isActive())
                    <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}">
                        @csrf
                        <button class="btn-danger text-xs">Suspend</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.tenants.activate', $tenant) }}">
                        @csrf
                        <button class="btn text-xs text-white bg-emerald-700 hover:bg-emerald-600 focus:ring-emerald-500">Activate</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-5 animate-fade-in">

        @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-emerald-900/30 border border-emerald-700/50 text-emerald-400 rounded-xl text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        <!-- Metrics -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Members',   'value' => $metrics['users'],      'color' => 'brand'],
                ['label' => 'Mailboxes', 'value' => $metrics['mailboxes'],  'color' => 'violet'],
                ['label' => 'Messages',  'value' => $metrics['messages'],   'color' => 'emerald'],
                ['label' => 'Storage',   'value' => $metrics['storage_mb'] . ' MB', 'color' => 'amber'],
            ] as $metric)
            <div class="stat-card text-center justify-center flex-col gap-1">
                <div class="text-2xl font-bold text-white">{{ $metric['value'] }}</div>
                <div class="stat-card-label">{{ $metric['label'] }}</div>
            </div>
            @endforeach
        </div>

        <!-- Details -->
        <div class="card p-6">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-4">Tenant Details</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="flex justify-between items-center py-2 border-b border-surface-700/50">
                    <span class="text-slate-500">Plan</span>
                    <span class="badge-gray capitalize">{{ $tenant->plan }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-surface-700/50">
                    <span class="text-slate-500">Status</span>
                    @if($tenant->isActive())
                        <span class="badge-green">Active</span>
                    @else
                        <span class="badge-red">{{ ucfirst($tenant->status) }}</span>
                    @endif
                </div>
                <div class="flex justify-between items-center py-2 border-b border-surface-700/50">
                    <span class="text-slate-500">Owner</span>
                    <span class="text-slate-300 font-medium">{{ $tenant->owner?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-surface-700/50">
                    <span class="text-slate-500">Owner Email</span>
                    <span class="text-slate-300 font-medium">{{ $tenant->owner?->email ?? '—' }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-slate-500">Created</span>
                    <span class="text-slate-300">{{ $tenant->created_at->toFormattedDateString() }}</span>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <a href="{{ route('admin.members.index', $tenant) }}"
               class="btn-primary text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Manage Members
            </a>
        </div>

    </div>
</x-app-layout>
