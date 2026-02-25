<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('external-mailboxes.index') }}" class="text-slate-400 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="page-title">Connect External Mailbox</h1>
                <p class="page-subtitle">Configure IMAP credentials to sync a catch-all mailbox</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl animate-fade-in" x-data="imapConnectionTest()">
        <form method="POST" action="{{ route('external-mailboxes.store') }}" class="card p-6 space-y-6" id="mailbox-form">
            @csrf
            
            <div>
                <label for="email" class="block text-sm font-medium text-slate-300 mb-1">Email Address</label>
                <input type="email" name="email" id="email" x-model="formData.email"
                       value="{{ old('email') }}" required
                       placeholder="catch-all@yourdomain.com"
                       class="form-input w-full">
            </div>

            <div class="grid grid-cols-6 gap-4 border-t border-surface-700 pt-6 mt-2">
                <div class="col-span-6 sm:col-span-4">
                    <label for="host" class="block text-sm font-medium text-slate-300 mb-1">IMAP Host</label>
                    <input type="text" name="host" id="host" x-model="formData.host"
                           value="{{ old('host', 'imap.hostinger.com') }}" required
                           class="form-input w-full">
                </div>

                <div class="col-span-3 sm:col-span-1">
                    <label for="port" class="block text-sm font-medium text-slate-300 mb-1">Port</label>
                    <input type="number" name="port" id="port" x-model="formData.port"
                           value="{{ old('port', 993) }}" required
                           class="form-input w-full">
                </div>

                <div class="col-span-3 sm:col-span-1">
                    <label for="encryption" class="block text-sm font-medium text-slate-300 mb-1">Security</label>
                    <select name="encryption" id="encryption" x-model="formData.encryption" class="form-input w-full">
                        <option value="ssl" @selected(old('encryption', 'ssl') == 'ssl')>SSL</option>
                        <option value="tls" @selected(old('encryption') == 'tls')>TLS</option>
                        <option value="none" @selected(old('encryption') == 'none')>None</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <label for="username" class="block text-sm font-medium text-slate-300 mb-1">Username</label>
                    <input type="text" name="username" id="username" x-model="formData.username"
                           value="{{ old('username') }}" required
                           placeholder="Usually your email"
                           class="form-input w-full">
                </div>

                <div class="col-span-2 sm:col-span-1">
                    <label for="password" class="block text-sm font-medium text-slate-300 mb-1">Password / App Password</label>
                    <input type="password" name="password" id="password" x-model="formData.password"
                           required
                           class="form-input w-full">
                           <p class="text-xs text-slate-500 mt-1">Stored securely encrypted.</p>
                </div>
            </div>

            <!-- Test Connection Result -->
            <div x-show="testResult !== null" 
                 :class="{ 'bg-emerald-900/30 border-emerald-700/50 text-emerald-400': testSuccess, 'bg-red-900/30 border-red-700/50 text-red-400': !testSuccess }"
                 class="p-4 border rounded-xl text-sm" style="display:none;">
                <p x-text="testMessage"></p>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-surface-700">
                <button type="button" @click="testConnection()" :disabled="isTesting" class="btn-secondary">
                    <span x-show="!isTesting">Test Connection</span>
                    <span x-show="isTesting" class="flex gap-2 items-center">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Testing...
                    </span>
                </button>
                <button type="submit" class="btn-primary">Connect Mailbox</button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('imapConnectionTest', () => ({
                formData: {
                    email: '{{ old('email') }}',
                    host: '{{ old('host', 'imap.hostinger.com') }}',
                    port: '{{ old('port', 993) }}',
                    encryption: '{{ old('encryption', 'ssl') }}',
                    username: '{{ old('username') }}',
                    password: ''
                },
                isTesting: false,
                testResult: null,
                testSuccess: false,
                testMessage: '',

                async testConnection() {
                    if(!this.formData.host || !this.formData.username || !this.formData.password) {
                        this.testResult = true;
                        this.testSuccess = false;
                        this.testMessage = 'Please fill in Host, Username, and Password fields first.';
                        return;
                    }

                    this.isTesting = true;
                    this.testResult = null;
                    
                    try {
                        const response = await fetch('{{ route('external-mailboxes.test') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(this.formData)
                        });
                        
                        const result = await response.json();
                        this.testResult = true;
                        this.testSuccess = result.success;
                        this.testMessage = result.message;
                    } catch (error) {
                        this.testResult = true;
                        this.testSuccess = false;
                        this.testMessage = 'A network error occurred while testing the connection.';
                    } finally {
                        this.isTesting = false;
                    }
                }
            }))
        })
    </script>
    @endpush
</x-app-layout>
