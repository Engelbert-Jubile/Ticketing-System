<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'Ticketing System') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
