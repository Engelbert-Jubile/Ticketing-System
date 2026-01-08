@props([
    'to' => null,
    'text' => 'Kembali',
    'icon' => 'arrow-left',
])

@php
    $curr     = url()->current();
    $prev     = url()->previous();
    $fallback = app('router')->has('dashboard') ? route('dashboard') : url('/dashboard');
    $from     = request()->query('from');

    if ($to && $to !== $curr) {
        $target = $to;
    } elseif ($from && $from !== $curr) {
        $target = $from;
    } elseif ($prev && $prev !== $curr) {
        $target = $prev;
    } else {
        $target = $fallback;
    }

    $iconSvg = match ($icon) {
        'home' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path d="M11.47 3.21a.75.75 0 0 1 1.06 0l8.25 8.25a.75.75 0 1 1-1.06 1.06L19.5 12.4v7.1a.75.75 0 0 1-.75.75H15a.75.75 0 0 1-.75-.75v-3.5a1.75 1.75 0 0 0-1.75-1.75h-1a1.75 1.75 0 0 0-1.75 1.75v3.5A.75.75 0 0 1 8 20.25H5.25a.75.75 0 0 1-.75-.75v-7.1l-1.22 1.12a.75.75 0 1 1-1.02-1.1l8.21-8.21Z" /></svg>',
        default => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M12.293 16.293a1 1 0 0 1-1.414 1.414l-6-6a1 1 0 0 1 0-1.414l6-6A1 1 0 1 1 12.293 5.707L7.414 10l4.879 4.293Z" clip-rule="evenodd" /></svg>',
    };
@endphp

<a href="{{ $target }}" class="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white/90 px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:border-indigo-300 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-100 dark:hover:border-indigo-400 dark:hover:text-indigo-300">
  {!! $iconSvg !!}
  <span>{{ $text }}</span>
</a>
