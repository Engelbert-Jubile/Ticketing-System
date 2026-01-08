<!DOCTYPE html>
<html lang="en" class="transition-colors duration-300">

<head>
    <meta charset="UTF-8">
    <title>TICKORA &mdash; Ticket Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @livewireStyles
    <style>
        :root {
            --brand-blue: #123b7a;
            --brand-orange: #f5862a;
            --brand-k-stem: 34%;
            --brand-k-epsilon: 1px;
        }

        .icon-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 7rem;
            width: 7rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.75);
            box-shadow: 0 22px 45px -28px rgba(59, 130, 246, 0.65);
            border: 1px solid rgba(96, 165, 250, 0.4);
            overflow: hidden;
        }

        .icon-image {
            position: relative;
            height: 4.75rem;
            width: 4.75rem;
            z-index: 1;
        }

        .icon-glow {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(96, 165, 250, 0.4), transparent 60%);
            animation: pulseGlow 2.8s ease-in-out infinite;
            z-index: 0;
        }

        @keyframes pulseGlow {

            0%,
            100% {
                transform: scale(0.9);
                opacity: 0.55;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.9;
            }
        }

        .tms-title {
            color: var(--brand-blue);
        }

        .tms-title .accent {
            display: inline-block;
            color: var(--brand-orange);
            background: none;
            -webkit-text-fill-color: currentColor;
        }

        .tms-title-main {
            color: var(--brand-blue);
        }

        .tms-title .accent-k {
            position: relative;
            display: inline-block;
            color: var(--brand-orange);
            -webkit-text-fill-color: currentColor;
            line-height: 1;
        }

        .tms-title .accent-k::after {
            content: attr(data-letter);
            position: absolute;
            inset: 0;
            color: var(--brand-blue);
            -webkit-text-fill-color: currentColor;
            clip-path: inset(0 calc(100% - (var(--brand-k-stem) + var(--brand-k-epsilon))) 0 0);
            pointer-events: none;
        }

    .tms-subtitle {
        color: var(--brand-blue);
        letter-spacing: 0.08em;
    }
    </style>

    @php
        $announcement = $announcement ?? ['enabled' => false];
        $maintenance = $maintenance ?? ['enabled' => false];
        $maintenanceEnabled = (bool) ($maintenance['enabled'] ?? false);
        $maintenanceMessage = $maintenance['message'] ?? 'The system is temporarily unavailable while we perform maintenance.';
        $announcementEnabled = (bool) ($announcement['enabled'] ?? false);
        $announcementTitle = $announcement['title'] ?? 'Announcement';
        $announcementBody = $announcement['body'] ?? ($announcement['message'] ?? null);
        $announcementStart = $announcement['start_at'] ?? ($announcement['starts_at'] ?? null);
        $announcementEnd = $announcement['end_at'] ?? ($announcement['ends_at'] ?? null);
        $announcementWindow = ($announcementStart || $announcementEnd)
            ? ($announcementStart ?: 'Now').' - '.($announcementEnd ?: 'Open')
            : null;
    @endphp
</head>

<body class="font-sans antialiased">
    <div class="relative min-h-screen overflow-hidden bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute -top-24 left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-blue-200/70 blur-3xl dark:bg-blue-900/30"></div>
            <div class="absolute -bottom-28 -left-28 h-80 w-80 rounded-full bg-amber-200/70 blur-3xl dark:bg-amber-900/25"></div>
            <div class="absolute top-1/3 -right-28 h-96 w-96 rounded-full bg-indigo-200/60 blur-3xl dark:bg-indigo-900/25"></div>
        </div>

        @if($maintenanceEnabled)
        <div class="absolute inset-x-0 top-8 z-10 flex justify-center px-4">
            <div class="flex w-full max-w-4xl items-center gap-3 rounded-2xl border border-indigo-200/70 bg-white/90 px-5 py-4 text-slate-900 shadow-xl shadow-indigo-200/50 ring-1 ring-indigo-100/80 backdrop-blur dark:border-indigo-500/40 dark:bg-slate-900/90 dark:text-white dark:ring-indigo-500/40">
                <span class="material-icons text-2xl text-indigo-600 dark:text-indigo-200">lock</span>
                <div class="text-left">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700 dark:text-indigo-200">Maintenance Mode</p>
                    <p class="text-sm">{{ $maintenanceMessage }}</p>
                </div>
            </div>
        </div>
        @endif

        @if($announcementEnabled)
        <div class="relative z-10 flex items-center justify-center px-4 pt-10">
            <div class="flex w-full max-w-5xl items-center gap-4 overflow-hidden rounded-3xl border border-slate-200/70 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 p-[1px] shadow-2xl shadow-blue-500/20 backdrop-blur">
                <div class="h-full w-1 rounded-full bg-white/60"></div>
                <div class="flex w-full items-center justify-between gap-4 rounded-[24px] bg-white/90 px-5 py-4 text-left ring-1 ring-white/70 backdrop-blur dark:bg-slate-900/90 dark:ring-slate-800/80">
                    <div class="flex items-start gap-3 text-slate-900 dark:text-white">
                        <span class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-100 via-white to-amber-200 text-amber-800 shadow-sm ring-1 ring-amber-200/60 dark:from-amber-700/50 dark:via-slate-800 dark:to-amber-800/30 dark:text-amber-50 dark:ring-amber-600/40">
                            <span class="material-icons text-lg">campaign</span>
                        </span>
                        <div class="space-y-1">
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-700 dark:text-amber-100">Announcement</p>
                            <p class="text-lg font-semibold leading-tight text-slate-900 dark:text-white">{{ $announcementTitle }}</p>
                            @if($announcementBody)
                                <p class="text-sm text-slate-700 dark:text-slate-200">{{ $announcementBody }}</p>
                            @endif
                            @if($announcementWindow)
                                <p class="text-xs font-medium text-blue-700/80 dark:text-blue-200/80">{{ $announcementWindow }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-blue-900/80 dark:text-blue-100/80">
                        <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-2 text-blue-700 ring-1 ring-blue-100 dark:bg-blue-900/40 dark:text-blue-100 dark:ring-blue-700/50">
                            <span class="material-icons text-base">schedule</span>
                            <span>{{ $announcementStart ?: 'Now' }} @if($announcementEnd) — {{ $announcementEnd }} @endif</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="relative flex min-h-screen items-center justify-center px-4 py-12">
            <div class="flex w-full max-w-3xl flex-col items-center justify-center gap-8 text-center">
        <div class="flex flex-col items-center gap-6">
            <div class="icon-wrapper">
                <span class="icon-glow"></span>
                <img src="https://img.icons8.com/?size=256&id=5LdqaP8dgiOs&format=png" alt="Ticket icon" class="icon-image" loading="lazy" />
            </div>
            <h1 class="flex flex-col gap-1 tms-title">
                <span class="tms-title-main text-4xl font-bold tracking-tight sm:text-5xl">TIC<span class="accent-k" data-letter="K">K</span>ORA</span>
                <span class="tms-subtitle text-xl font-medium tracking-wide sm:text-2xl">Ticket Management System</span>
            </h1>
        </div>

        <div class="w-full max-w-md rounded-3xl bg-white p-10 text-center shadow-lg shadow-blue-200/60 ring-1 ring-blue-100/60 {{ $maintenanceEnabled ? 'opacity-80' : '' }}">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 shadow-sm">
                <span class="material-icons text-3xl">login</span>
            </div>
            <h2 class="text-2xl font-semibold text-slate-900">Masuk ke akun Anda</h2>
            <p class="mt-2 text-sm text-slate-600">
                Akses seluruh fitur Dashboard, Ticket, Task, Project.
            </p>
            <div class="mt-6 grid gap-3">
                <a wire:navigate.hover href="{{ route('login', ['locale' => app()->getLocale() ?? config('app.locale', 'en')]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-600 bg-white px-6 py-3 text-sm font-semibold text-blue-600 transition hover:bg-blue-50">
                    <span class="material-icons text-base">login</span>
                    <span>Login</span>
                </a>
                @if($maintenanceEnabled)
                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-6 py-3 text-sm font-semibold text-slate-500 shadow-inner" disabled aria-disabled="true">
                        <span class="material-icons text-base">lock_person</span>
                        <span>Register (disabled)</span>
                    </button>
                @else
                    <a wire:navigate.hover href="{{ route('register', ['locale' => app()->getLocale() ?? config('app.locale', 'en')]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-600 bg-white px-6 py-3 text-sm font-semibold text-emerald-600 transition hover:bg-emerald-50">
                        <span class="material-icons text-base">person_add</span>
                        <span>Register</span>
                    </a>
                @endif
                @if($maintenanceEnabled)
                <p class="text-[11px] font-medium uppercase tracking-[0.2em] text-slate-500">Tidak dapat Login selama maintenance.</p>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-4 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-700">
            <div class="flex flex-wrap items-center justify-center gap-3">
                <span class="inline-flex w-60 items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2 shadow-sm shadow-slate-200/70">
                    <span class="mr-3 text-blue-600">
                        <span class="material-icons text-xl">workspace_premium</span>
                    </span>
                    <span class="flex flex-col text-slate-900 leading-tight">
                        <span>Team</span>
                        <span>Workspace</span>
                    </span>
                </span>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <span class="inline-flex w-60 items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2 shadow-sm shadow-slate-200/70">
                    <span class="mr-3 text-emerald-600">
                        <span class="material-icons text-xl">groups</span>
                    </span>
                    <span class="flex flex-col text-slate-900 leading-tight">
                        <span>Team</span>
                        <span>Collaboration</span>
                    </span>
                </span>
                <span class="inline-flex w-60 items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2 shadow-sm shadow-slate-200/70">
                    <span class="mr-3 text-amber-500">
                        <span class="material-icons text-xl">bolt</span>
                    </span>
                    <span class="flex flex-col text-slate-900 leading-tight">
                        <span>Team</span>
                        <span>Operations</span>
                    </span>
                </span>
            </div>
        </div>

        <div class="space-y-1 text-xs text-slate-500">
            <p>Made with <span aria-hidden="true">❤️</span> By IT KFTD</p>
            <p>
                Copyright ©2025
                <a href="https://kftd.co.id/" class="font-semibold text-blue-600 hover:text-blue-700" target="_blank" rel="noopener">
                    Kimia Farma Trading &amp; Distribution
                </a>.
                All rights reserved.
            </p>
        </div>
            </div>
        </div>
    </div>

    @livewireScripts
</body>

</html>
