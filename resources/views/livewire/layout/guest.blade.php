<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="bg-gray-100 text-gray-900 antialiased">

    <div class="min-h-screen flex flex-col justify-center items-center px-4">
        {{-- Optional: Logo --}}
        <div class="mb-6">
            <a href="/" class="text-xl font-bold text-blue-600">
                {{ config('app.name', 'MyApp') }}
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
