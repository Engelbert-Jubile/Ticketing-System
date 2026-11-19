<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.browser_title') }}</title>

    @vite(['resources/css/app.css'])
    @livewireStyles
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ filemtime(public_path('favicon.svg')) }}">
</head>
<body class="bg-gray-100 text-gray-900 antialiased">

    <div class="min-h-screen flex flex-col justify-center items-center px-4">
        {{-- Optional: Logo --}}
        <div class="mb-6">
            <a href="/" class="text-xl font-bold text-blue-600">
                <x-brand-logo />
            </a>
        </div>

        {{-- Page Content --}}
        <div class="w-full max-w-md bg-white p-6 rounded-lg shadow-md">
            {{ $slot }}
        </div>
    </div>

    @livewireScripts
</body>
</html>
