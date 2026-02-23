<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $tenant->name }}
                <span class="text-sm font-normal text-gray-400 ml-2">{{ $tenant->slug }}</span>
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.tenants.edit', $tenant) }}"
                   class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-sm rounded-md shadow-sm hover:bg-gray-50">Edit</a>
                @if($tenant->isActive())
                    <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}">
                        @csrf
                        <button class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-md shadow-sm hover:bg-red-700">Suspend</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.tenants.activate', $tenant) }}">
                        @csrf
                        <button class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-md shadow-sm hover:bg-green-700">Activate</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-3 bg-green-50 border border-green-200 text-green-800 rounded text-sm">{{ session('success') }}</div>
            @endif

            {{-- Metrics --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach([
                    ['label'=>'Members',    'value'=> $metrics['users']],
                    ['label'=>'Mailboxes',  'value'=> $metrics['mailboxes']],
                    ['label'=>'Messages',   'value'=> $metrics['messages']],
                    ['label'=>'Storage',    'value'=> $metrics['storage_mb'] . ' MB'],
                ] as $metric)
                <div class="bg-white rounded-lg shadow-sm p-5 text-center">
                    <div class="text-2xl font-bold text-indigo-600">{{ $metric['value'] }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $metric['label'] }}</div>
                </div>
                @endforeach
            </div>

            {{-- Details --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Plan:</span> <span class="font-medium capitalize">{{ $tenant->plan }}</span></div>
                <div><span class="text-gray-500">Status:</span>
                    <span class="font-medium {{ $tenant->isActive() ? 'text-green-600' : 'text-red-600' }}">{{ ucfirst($tenant->status) }}</span>
                </div>
                <div><span class="text-gray-500">Owner:</span> <span class="font-medium">{{ $tenant->owner?->name ?? '—' }}</span></div>
                <div><span class="text-gray-500">Owner Email:</span> <span class="font-medium">{{ $tenant->owner?->email ?? '—' }}</span></div>
                <div><span class="text-gray-500">Created:</span> <span class="font-medium">{{ $tenant->created_at->toFormattedDateString() }}</span></div>
            </div>

            {{-- Members link --}}
            <div class="text-right">
                <a href="{{ route('admin.members.index', $tenant) }}"
                   class="text-sm text-indigo-600 hover:underline">Manage Members →</a>
            </div>
        </div>
    </div>
</x-app-layout>
