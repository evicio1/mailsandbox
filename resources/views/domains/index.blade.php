<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h1 class="page-title">Domains</h1>
                <p class="page-subtitle">Manage custom domains for your mailboxes</p>
            </div>
            <a href="{{ route('domains.create') }}" class="btn-primary">
                Add Domain
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
        
        @if(session('error'))
        <div class="flex items-center gap-3 p-4 bg-red-900/30 border border-red-700/50 text-red-400 rounded-xl text-sm">
            {{ session('error') }}
        </div>
        @endif
        
        @if(session('info'))
        <div class="flex items-center gap-3 p-4 bg-brand-900/30 border border-brand-700/50 text-brand-400 rounded-xl text-sm">
            {{ session('info') }}
        </div>
        @endif

        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Catch-All</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($domains as $domain)
                    <tr>
                        <td class="font-medium text-white">{{ $domain->domain }}</td>
                        <td>
                            @if($domain->is_platform_provided)
                                <span class="badge-brand">Platform Subdomain</span>
                            @else
                                <span class="badge-gray">Custom Domain</span>
                            @endif
                        </td>
                        <td>
                            @if($domain->is_verified)
                                <span class="badge-green">Verified</span>
                            @else
                                <span class="badge-amber">Pending Verification</span>
                            @endif
                        </td>
                        <td>
                            @if($domain->catch_all_enabled)
                                <span class="badge-green">Enabled</span>
                            @else
                                <span class="badge-gray">Disabled</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-3">
                                @if(!$domain->is_verified)
                                <a href="{{ route('domains.edit', $domain) }}" class="text-xs text-brand-400 hover:text-brand-300 font-medium transition">Complete Setup</a>
                                @else
                                <a href="{{ route('domains.edit', $domain) }}" class="text-xs text-brand-400 hover:text-brand-300 font-medium transition">Settings</a>
                                @endif
                                
                                <form method="POST" action="{{ route('domains.destroy', $domain) }}" onsubmit="return confirm('Remove this domain? This may break existing inboxes using it.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-red-400 hover:text-red-300 font-medium transition">Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-slate-500">No domains added yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
