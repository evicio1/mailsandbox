<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('domains.index') }}" class="text-slate-400 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="page-title">Add Domain</h1>
                <p class="page-subtitle">Connect your custom domain</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl animate-fade-in">
        <form method="POST" action="{{ route('domains.store') }}" class="card p-6 space-y-6">
            @csrf
            
            <div>
                <label for="domain" class="block text-sm font-medium text-slate-300 mb-1">Domain Name</label>
                <input type="text" name="domain" id="domain" 
                       value="{{ old('domain') }}"
                       placeholder="e.g. mail.yourcompany.com" 
                       required
                       class="form-input w-full">
                @error('domain')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
                <p class="text-slate-500 text-xs mt-2">
                    Enter the domain or subdomain you want to use for virtual inboxes. You will need to add DNS records to verify ownership.
                </p>
            </div>

            <div class="flex justify-end pt-4 border-t border-surface-700">
                <button type="submit" class="btn-primary">Add Domain</button>
            </div>
        </form>
    </div>
</x-app-layout>
