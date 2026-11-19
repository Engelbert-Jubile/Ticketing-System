{{-- resources/views/layouts/guest.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.public-theme-bootstrap')
    <meta charset="utf-8">
    <title>{{ config('app.browser_title') }}</title>

    @vite(['resources/css/app.css', 'resources/css/public.css'])
    @livewireStyles
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ filemtime(public_path('favicon.svg')) }}">
</head>
<body class="bg-gray-100 dark:bg-gray-900 flex items-center justify-center min-h-screen">

    <main class="w-full max-w-md p-6 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow-lg">
        {{-- ① Jika halaman memakai @section('content') --}}
        @hasSection('content')
            @yield('content')

        {{-- ② Jika halaman berasal dari Livewire ->layout() --}}
        @elseif (!empty($slot))
            {{ $slot }}
        @endif
    </main>

    @livewireScripts(['navigate' => false])</body>
</html>
