<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ config('app.slogan', 'MailEyez - Smart Virtual Inboxes for Teams') }}">

        <title>{{ config('app.name', 'MailEyez') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-surface-900 text-slate-100">
        <div class="min-h-screen flex">

            <!-- ─── Left: Brand Panel ─────────────────── -->
            <div class="hidden lg:flex lg:w-2/5 xl:w-1/2 auth-panel-brand relative overflow-hidden items-center justify-center p-12">
                <!-- Background decoration -->
                <div class="absolute inset-0 overflow-hidden">
                    <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full opacity-20"
                         style="background: radial-gradient(circle, #818cf8 0%, transparent 70%)"></div>
                    <div class="absolute -bottom-40 -left-20 w-80 h-80 rounded-full opacity-10"
                         style="background: radial-gradient(circle, #a78bfa 0%, transparent 70%)"></div>
                </div>

                <div class="relative z-10 max-w-sm">
                    <!-- Logo -->
                    <div class="flex items-center gap-3 mb-10">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-glow-brand"
                             style="background: rgba(255,255,255,0.15); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.2)">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-2xl font-bold text-white">{{ config('app.name', 'MailEyez') }}</span>
                    </div>

                    <!-- Headline -->
                    <h1 class="text-4xl font-bold text-white leading-tight mb-4">
                        Smart Virtual Inboxes<br>
                        <span class="text-brand-300">for Teams</span>
                    </h1>
                    <p class="text-brand-200/70 text-base leading-relaxed mb-10">
                        Capture, organize and search your team's incoming mail — OTPs, registrations, and notifications, all in one place.
                    </p>

                    <!-- Feature pills -->
                    <div class="flex flex-wrap gap-2">
                        @foreach(['OTP Detection', 'Multi-tenant', 'IMAP Import', 'Audit Logs'] as $feature)
                        <span class="px-3 py-1 text-xs font-medium rounded-full text-brand-200"
                              style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15)">
                            {{ $feature }}
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- ─── Right: Form Panel ─────────────────── -->
            <div class="flex-1 flex flex-col items-center justify-center px-6 py-12 bg-surface-900">
                <!-- Mobile logo -->
                <div class="lg:hidden flex items-center gap-2 mb-10">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center bg-gradient-brand">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-white">{{ config('app.name', 'MailEyez') }}</span>
                </div>

                <div class="w-full max-w-md">
                    {{ $slot }}
                </div>
            </div>

        </div>
    </body>
</html>
