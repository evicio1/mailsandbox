<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('domains.index') }}" class="text-slate-400 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="page-title">Domain Settings</h1>
                <p class="page-subtitle">{{ $domain->domain }}</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl space-y-6 animate-fade-in">

        @if(session('success'))
        <div class="flex items-center gap-3 p-4 bg-emerald-900/30 border border-emerald-700/50 text-emerald-400 rounded-xl text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="flex items-center gap-3 p-4 bg-red-900/30 border border-red-700/50 text-red-400 rounded-xl text-sm">
            {{ session('error') }}
        </div>
        @endif

        @if(!$domain->is_verified)
        <div class="card p-6 border border-amber-500/30">
            <h2 class="text-lg font-semibold text-white mb-2 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                Verify Domain Ownership
            </h2>
            <p class="text-slate-400 text-sm mb-6">
                To start receiving emails, you must prove you own <strong>{{ $domain->domain }}</strong> by adding a TXT record to its DNS settings.
            </p>

            <div class="bg-surface-900 rounded-lg p-4 mb-6 font-mono text-sm text-slate-300 overflow-x-auto border border-surface-700">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-3 text-slate-500">Type</div>
                    <div class="col-span-9">TXT</div>
                    
                    <div class="col-span-3 text-slate-500">Name / Host</div>
                    <div class="col-span-9">@ (or leave blank)</div>
                    
                    <div class="col-span-3 text-slate-500">Value</div>
                    <div class="col-span-9 font-bold text-white break-all">{{ $domain->verification_token }}</div>
                </div>
            </div>
            
            <p class="text-slate-500 text-xs mb-6">
                DNS changes can take up to 24 hours to propagate, but usually happen within minutes.
            </p>

            <form method="POST" action="{{ route('domains.verify', $domain) }}">
                @csrf
                <button type="submit" class="btn-primary w-full justify-center">Verify Now</button>
            </form>
        </div>
        @else
        <div class="card p-6 border border-emerald-500/30">
            <h2 class="text-lg font-semibold text-white mb-2 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Domain Verified
            </h2>
            <p class="text-slate-400 text-sm mb-6">
                This domain is verified and ready to receive emails. Make sure your MX records are pointing to our mail servers.
            </p>
            
            <div class="bg-surface-900 rounded-lg p-4 font-mono text-sm text-slate-300 overflow-x-auto border border-surface-700">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-3 text-slate-500">Type</div>
                    <div class="col-span-9">MX</div>
                    
                    <div class="col-span-3 text-slate-500">Name / Host</div>
                    <div class="col-span-9">@ (or leave blank)</div>
                    
                    <div class="col-span-3 text-slate-500">Value</div>
                    <div class="col-span-9 font-bold text-white">10 {{ config('imap.domain', 'evicio.site') }}</div>
                </div>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('domains.update', $domain) }}" class="card p-6 space-y-6">
            @csrf
            @method('PUT')
            
            <div>
                <h3 class="text-lg font-semibold text-white mb-1">Routing Settings</h3>
                <p class="text-slate-400 text-sm mb-4">Configure how we handle emails sent to this domain.</p>
                
                <label class="flex items-start gap-3 p-4 bg-surface-800 rounded-xl border border-surface-700 cursor-pointer hover:bg-surface-750 transition">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="catch_all_enabled" value="1" 
                               {{ $domain->catch_all_enabled ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-surface-600 bg-surface-900 text-brand-500 focus:ring-brand-500 focus:ring-offset-surface-800">
                    </div>
                    <div>
                        <div class="text-sm font-medium text-white">Enable Catch-All</div>
                        <div class="text-xs text-slate-400 mt-1">
                            Automatically create a new inbox when an email is received for a new address at this domain (e.g., anything@{{ $domain->domain }}). If disabled, emails to unknown addresses will be ignored.
                        </div>
                    </div>
                </label>
            </div>

            <div class="flex justify-end pt-4 border-t border-surface-700">
                <button type="submit" class="btn-primary">Save Settings</button>
            </div>
        </form>

    </div>
</x-app-layout>
