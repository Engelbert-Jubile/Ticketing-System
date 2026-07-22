<!DOCTYPE html>
@php
  $routeName = (string) optional(request()->route())->getName();
  $initialTitle = match (true) {
    str_starts_with($routeName, 'workflows.') => 'Workflows - Tickora',
    $routeName === 'dashboard' => 'Dashboard - Tickora',
    default => 'Tickora',
  };
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ $initialTitle }}</title>
    <meta name="application-name" content="Tickora">
    <meta name="apple-mobile-web-app-title" content="Tickora">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ filemtime(public_path('favicon.svg')) }}">
<link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @routes(nonce: $cspNonce ?? request()->attributes->get('csp_nonce'))
    @vite(['resources/js/app.js'])
    @livewireStyles
    @stack('styles')
    @inertiaHead
  </head>
  <body class="font-sans antialiased min-h-screen bg-slate-100 dark:bg-slate-900 text-slate-900 dark:text-slate-100">
    @inertia

    @stack('modals')
    @stack('scripts')
    @livewireScripts
  </body>
</html>
