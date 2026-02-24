<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ config('app.slogan', 'MailEyez - Smart Virtual Inboxes for Teams') }}">

        <title>@yield('title', config('app.name', 'MailEyez'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans antialiased bg-surface-900 text-slate-100">

        <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">

            <!-- Mobile overlay -->
            <div
                x-show="sidebarOpen"
                x-transition.opacity
                @click="sidebarOpen = false"
                class="fixed inset-0 bg-black/60 z-20 md:hidden"
                style="display:none"
            ></div>

            <!-- ─── Sidebar ─────────────────────────────── -->
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
                class="app-sidebar transition-transform duration-300 ease-in-out"
            >
                <!-- Logo -->
                <div class="flex items-center gap-3 px-5 py-5 border-b border-white/5">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-gradient-brand shadow-glow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-white">{{ config('app.name', 'MailEyez') }}</div>
                        <div class="text-[10px] text-slate-500 uppercase tracking-wider">Virtual Inboxes</div>
                    </div>
                </div>

                <!-- Nav Links -->
                <nav class="flex-1 py-4 space-y-0.5 overflow-y-auto">
                    <div class="px-4 pb-2 pt-1 text-[10px] font-semibold uppercase tracking-widest text-slate-600">Main</div>

                    <a href="{{ route('dashboard') }}"
                       class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Dashboard
                    </a>

                    <a href="{{ route('mailboxes.index') }}"
                       class="sidebar-link {{ request()->routeIs('mailboxes.*') ? 'active' : '' }}">
                        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        Mailboxes
                    </a>

                    @if(Auth::user()->isSuperAdmin() || (Auth::user()->tenant_id && (Auth::user()->isTenantAdmin() || Auth::user()->isSuperAdmin())))
                    <div class="px-4 pb-2 pt-4 text-[10px] font-semibold uppercase tracking-widest text-slate-600">Admin</div>
                    @endif

                    @if(Auth::user()->isSuperAdmin())
                    <a href="{{ route('admin.tenants.index') }}"
                       class="sidebar-link {{ request()->routeIs('admin.tenants.*') ? 'active' : '' }}">
                        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Tenants
                    </a>
                    @endif

                    @if(Auth::user()->tenant_id && (Auth::user()->isTenantAdmin() || Auth::user()->isSuperAdmin()))
                    <a href="{{ route('admin.members.index', Auth::user()->tenant_id) }}"
                       class="sidebar-link {{ request()->routeIs('admin.members.*') ? 'active' : '' }}">
                        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Members
                    </a>
                    @endif
                </nav>

                <!-- User section at bottom -->
                <div class="border-t border-white/5 p-4">
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="flex items-center gap-3 w-full rounded-lg px-2 py-2 hover:bg-surface-800 transition">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                 style="background: linear-gradient(135deg, #4f46e5, #7c3aed)">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <div class="flex-1 text-left min-w-0">
                                <div class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</div>
                                <div class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-500 flex-shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 transform scale-100"
                             x-transition:leave-end="opacity-0 transform scale-95"
                             @click.away="open = false"
                             class="absolute bottom-full left-0 mb-2 w-full bg-surface-700 border border-surface-600 rounded-xl shadow-xl py-1"
                             style="display:none">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-300 hover:text-white hover:bg-surface-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Profile
                            </a>
                            <a href="{{ route('sessions.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-300 hover:text-white hover:bg-surface-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                                Sessions
                            </a>
                            <div class="border-t border-surface-600 mt-1 pt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-400 hover:text-red-300 hover:bg-surface-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- ─── Main Content ────────────────────────── -->
            <div class="flex-1 flex flex-col min-h-screen overflow-y-auto md:ml-[260px]">

                <!-- Top Bar -->
                <header class="app-topbar">
                    <div class="flex items-center gap-4">
                        <!-- Mobile hamburger -->
                        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-slate-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>

                        @isset($header)
                        <div class="text-slate-100">
                            {{ $header }}
                        </div>
                        @endisset
                    </div>

                    <!-- Right side: quick actions -->
                    <div class="flex items-center gap-3">
                        <span class="hidden sm:flex items-center gap-1.5 text-xs text-slate-500">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Live
                        </span>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
