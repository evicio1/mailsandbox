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
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-emerald-400 text-sm">
                Subscription successful! Thank you for upgrading.
            </div>
        @endif

        @if(request()->has('canceled'))
            <div class="p-4 bg-yellow-500/10 border border-yellow-500/20 rounded-lg text-yellow-400 text-sm">
                Checkout canceled. Your plan was not changed.
            </div>
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
                        @if($tenant->subscribed('default'))
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
                            @elseif($plan->price_id)
                                <form action="{{ route('billing.checkout', $plan->plan_id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full btn-primary text-sm justify-center">
                                        Subscribe
                                    </button>
                                </form>
                            @else
                                <a href="mailto:sales@maileyez.com" class="w-full flex justify-center items-center py-2 bg-surface-700 hover:bg-surface-600 text-white font-medium rounded-lg text-sm transition">
                                    Contact Sales
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
