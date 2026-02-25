<x-app-layout>
    <x-slot name="header">
        <div class="page-header mb-0">
            <div>
                <h1 class="page-title">Billing & Plans</h1>
                <p class="page-subtitle">Manage your virtual mailboxes subscription.</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8 animate-fade-in max-w-5xl">

        @if(session('error'))
            <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if(request()->has('success'))
            @if($tenant->current_plan_id === 'free' || !$tenant->subscribed('default'))
                <!-- Webhook hasn't processed yet -->
                <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-lg flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-500 animate-spin flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <div>
                        <h4 class="text-amber-400 font-medium text-sm">Activating Subscription...</h4>
                        <p class="text-amber-500/80 text-xs mt-1">Your payment was successful! We are currently syncing your new plan. This usually takes just a few seconds. Refresh the page to see your updated limits.</p>
                    </div>
                </div>
            @else
                <!-- Webhook already processed / they already have a paid plan -->
                <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-emerald-400 text-sm">
                    Subscription successful! Thank you for upgrading.
                </div>
            @endif
        @endif

        @if(request()->has('canceled'))
            <div class="p-4 bg-slate-500/10 border border-slate-500/20 rounded-lg text-slate-400 text-sm">
                Checkout canceled. Your plan was not changed.
            </div>
        @endif

        <!-- Cancel/Downgrade Warning -->
        @if($tenant->cancel_at_period_end && $tenant->current_period_end)
            <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-lg text-amber-500 text-sm mb-8">
                <div class="flex items-center gap-2 font-semibold mb-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Subscription Cancels Soon
                </div>
                Your subscription will remain active until the end of your billing cycle on <strong>{{ $tenant->current_period_end->format('F j, Y') }}</strong>. After this date, your account will be downgraded to the Free plan.
            </div>
        @elseif($tenant->pending_plan_id)
            @php
                $upcomingPlan = \App\Models\Plan::where('plan_id', $tenant->pending_plan_id)->first();
            @endphp
            @if($upcomingPlan)
                <div class="p-4 bg-blue-500/10 border border-blue-500/20 rounded-lg text-blue-400 text-sm mb-8">
                    <div class="flex items-center gap-2 font-semibold mb-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Plan Change Pending
                    </div>
                    Your subscription is scheduled to change to the <strong>{{ $upcomingPlan->name }}</strong> plan at the end of your current billing cycle on <strong>{{ $tenant->current_period_end->format('F j, Y') }}</strong>.
                </div>
            @endif
        @endif

        <!-- Current Plan -->
        <div class="card p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-white">Current Plan: <span class="capitalize text-brand-400">{{ $tenant->plan->name ?? 'Free' }}</span></h2>
                    <p class="text-sm text-slate-400 mt-1">
                        Inbox limit: {{ $tenant->inbox_limit === -1 ? 'Unlimited' : $tenant->inbox_limit }}
                        &middot;
                        Subscription Status: 
                        @if($tenant->cancel_at_period_end && $tenant->current_period_end)
                            <span class="text-amber-400 font-medium">Cancels {{ $tenant->current_period_end->format('M j') }}</span>
                        @elseif($tenant->subscribed('default'))
                            <span class="text-emerald-400 font-medium">Active</span>
                        @else
                            <span class="text-slate-400">None</span>
                        @endif
                    </p>
                </div>
                
                @if($tenant->stripe_id)
                <form action="{{ route('billing.portal') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Manage Billing
                    </button>
                </form>
                @endif
            </div>
        </div>

        <!-- Available Plans -->
        <div>
            <h3 class="text-lg font-semibold text-white mb-4">Available Plans</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($plans as $plan)
                    <div class="card p-6 border-2 {{ ($tenant->current_plan_id === $plan->plan_id) ? 'border-brand-500/50' : 'border-transparent' }} flex flex-col relative">
                        
                        @if($tenant->current_plan_id === $plan->plan_id)
                            <div class="absolute top-0 right-0 transform translate-x-2 -translate-y-2">
                                <span class="bg-brand-500 text-white text-[10px] font-bold uppercase tracking-wider py-1 px-2 rounded-full shadow-glow-sm">Current</span>
                            </div>
                        @endif

                        <h4 class="text-xl font-bold text-white mb-1">{{ $plan->name }}</h4>
                        <div class="text-sm text-slate-400 mb-4">Up to {{ $plan->inbox_limit === -1 ? 'unlimited' : $plan->inbox_limit }} active inboxes</div>
                        
                        <div class="mt-auto pt-6 border-t border-white/5">
                            @if($tenant->current_plan_id === $plan->plan_id)
                                <button disabled class="w-full py-2 bg-surface-700 text-slate-400 font-medium rounded-lg text-sm cursor-not-allowed">
                                    Current Plan
                                </button>
                            @elseif($plan->price_id && $tenant->subscribed('default'))
                                <form action="{{ route('billing.portal') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full btn-outline text-sm justify-center">
                                        Switch in Portal
                                    </button>
                                </form>
                            @elseif($plan->price_id)
                                <form action="{{ route('billing.checkout', $plan->plan_id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full btn-primary text-sm justify-center">
                                        Subscribe
                                    </button>
                                </form>
                            @elseif($plan->plan_id === 'free' && $tenant->subscribed('default'))
                                <form action="{{ route('billing.portal') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full btn-outline text-red-500/80 hover:text-red-500 border-red-500/50 hover:bg-red-500/10 text-sm justify-center transition">
                                        Cancel Subscription
                                    </button>
                                </form>
                            @elseif($plan->plan_id === 'free')
                                <button disabled class="w-full py-2 bg-surface-700 text-slate-400 font-medium rounded-lg text-sm cursor-not-allowed">
                                    Included
                                </button>
                            @else
                                <form action="{{ route('billing.contact-sales') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->plan_id }}">
                                    <button type="submit" class="w-full flex justify-center items-center py-2 bg-surface-700 hover:bg-surface-600 text-white font-medium rounded-lg text-sm transition">
                                        {{ \App\Models\SalesInquiry::where('tenant_id', $tenant->id)->where('status', 'new')->exists() ? 'Request Sent' : 'Contact Sales' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
