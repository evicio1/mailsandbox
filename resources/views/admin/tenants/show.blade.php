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

        @php
            $hasPaymentIssue = $tenant->subscribed('default') && ($tenant->subscription('default')->pastDue() || $tenant->subscription('default')->hasIncompletePayment());
            // High attempts: total mailboxes (including deleted) is much higher than the limit
            $highInboxAttempts = $tenant->inbox_limit !== -1 && $tenant->mailboxes()->withTrashed()->count() > ($tenant->inbox_limit * 2 + 5);
        @endphp

        @if($hasPaymentIssue || $highInboxAttempts)
            <div class="space-y-3">
                @if($hasPaymentIssue)
                    <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500 text-sm flex items-start gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <div>
                            <div class="font-semibold">Payment Issue Detected</div>
                            <p class="mt-1 text-red-400">This tenant has a past due subscription or incomplete payment. Please monitor or contact the owner.</p>
                        </div>
                    </div>
                @endif
                
                @if($highInboxAttempts)
                    <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-lg text-amber-500 text-sm flex items-start gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <div class="font-semibold">High Inbox Creation Volume</div>
                            <p class="mt-1 text-amber-400">This tenant has an unusually high number of total mailboxes compared to their plan limit. Monitor for potential API abuse.</p>
                        </div>
                    </div>
                @endif
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
                    <span class="badge-gray capitalize">{{ $tenant->plan->name ?? 'Free' }}</span>
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
                <div class="flex justify-between items-center py-2 border-b border-surface-700/50">
                    <span class="text-slate-500">Created</span>
                    <span class="text-slate-300">{{ $tenant->created_at->toFormattedDateString() }}</span>
                </div>
                <!-- Billing Details -->
                <div class="flex justify-between items-center py-2 border-b border-surface-700/50">
                    <span class="text-slate-500">Subscription Status</span>
                    @if($tenant->subscribed('default'))
                        @if($tenant->subscription('default')->pastDue())
                            <span class="badge-red">Past Due</span>
                        @elseif($tenant->subscription('default')->canceled())
                            <span class="badge-amber">Canceled</span>
                        @else
                            <span class="badge-green">Active</span>
                        @endif
                    @else
                        <span class="badge-gray">None</span>
                    @endif
                </div>
                <div class="flex justify-between items-center py-2 border-b border-surface-700/50">
                    <span class="text-slate-500">Stripe Customer</span>
                    @if($tenant->stripe_id)
                        <a href="https://dashboard.stripe.com/customers/{{ $tenant->stripe_id }}" target="_blank" class="text-brand-400 hover:underline">View in Stripe ↗</a>
                    @else
                        <span class="text-slate-500">—</span>
                    @endif
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-slate-500">Stripe Subscription</span>
                    @if($tenant->subscribed('default') && $tenant->subscription('default')->stripe_id)
                        <a href="https://dashboard.stripe.com/subscriptions/{{ $tenant->subscription('default')->stripe_id }}" target="_blank" class="text-brand-400 hover:underline">View in Stripe ↗</a>
                    @else
                        <span class="text-slate-500">—</span>
                    @endif
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
