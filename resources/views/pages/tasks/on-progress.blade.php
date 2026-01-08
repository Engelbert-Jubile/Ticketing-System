{{-- LEGACY (fallback) resources/views/pages/tasks/on-progress.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Tombol kembali --}}
    @include('components.back', ['to' => route('dashboard')])

    <h1 class="text-2xl font-bold mb-4 transition-colors duration-300">Task In Progress</h1>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-green-700
                    dark:bg-green-900/30 dark:border-green-700 dark:text-green-200 transition-colors duration-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- BUTTON UTAMA --}}
    <a href="{{ route('tasks.create', ['from' => request()->fullUrl()]) }}"
       class="mb-4 inline-flex items-center justify-center gap-2 rounded-lg
              bg-blue-600 hover:bg-blue-700 active:bg-blue-800
              dark:bg-blue-500 dark:hover:bg-blue-400 dark:active:bg-blue-600
              px-4 py-2 text-sm font-bold tracking-wide text-white dark:text-white subpixel-antialiased drop-shadow-sm
              shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 transition-colors duration-300">
        <span class="text-base leading-none">+</span>
        <span class="leading-none">Buat Task</span>
    </a>

    <div class="overflow-x-auto">
        <table class="w-full table-auto border-collapse">
            <thead>
                <tr class="bg-gray-200 text-left dark:bg-gray-700 dark:text-gray-100 transition-colors duration-300">
                    <th class="border p-2">#</th>
                    <th class="border p-2">Title</th>
                    <th class="border p-2">Description</th>
                    <th class="border p-2">Status</th>
                    <th class="border p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    @php
                        $statusRaw = $task->status instanceof \BackedEnum ? $task->status->value : $task->status;

                        // Base style badge (mirip report.blade.php)
                        $badgeBase = 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset transition-colors duration-300';

                        // Palet warna badge mengikuti report.blade.php
                        $statusStyles = [
                            'in_progress' => [
                                'label' => 'In Progress',
                                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                                'class' => 'bg-amber-200 text-amber-800 ring-amber-300 dark:bg-amber-600 dark:text-white dark:ring-amber-400'
                            ],
                            'completed' => [
                                'label' => 'Completed',
                                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>',
                                'class' => 'bg-green-200 text-green-800 ring-green-300 dark:bg-green-600 dark:text-white dark:ring-green-400'
                            ],
                            'pending' => [
                                'label' => 'Pending',
                                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                                'class' => 'bg-blue-200 text-blue-800 ring-blue-300 dark:bg-blue-600 dark:text-white dark:ring-blue-400'
                            ],
                            'open' => [
                                'label' => 'Open',
                                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9-4 9 4-9 4-9-4z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l9 4 9-4M3 12l9 4 9-4" /></svg>',
                                'class' => 'bg-gray-200 text-gray-800 ring-gray-300 dark:bg-gray-600 dark:text-white dark:ring-gray-400'
                            ],
                            'cancelled' => [
                                'label' => 'Cancelled',
                                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>',
                                'class' => 'bg-rose-200 text-rose-800 ring-rose-300 dark:bg-rose-600 dark:text-white dark:ring-rose-400'
                            ],
                        ];

                        $style = $statusStyles[$statusRaw] ?? [
                            'label' => \Illuminate\Support\Str::headline($statusRaw ?? 'Unknown'),
                            'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 6h.01" /></svg>',
                            'class' => 'bg-blue-200 text-blue-800 ring-blue-300 dark:bg-blue-600 dark:text-white dark:ring-blue-400',
                        ];

                        // ---- Deskripsi: strip semua tag & ringkas ----
                        $descPlain = trim(preg_replace('/\s+/u', ' ', strip_tags((string)($task->description ?? ''))));
                        $descShown = $descPlain !== '' ? \Illuminate\Support\Str::limit($descPlain, 160) : 'â€”';
                    @endphp

                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-300">
                        <td class="border p-2">{{ $loop->iteration }}</td>
                        <td class="border p-2">{{ $task->title }}</td>
                        <td class="border p-2">{{ $descShown }}</td>
                        <td class="border p-2">
                            <span class="{{ $badgeBase }} {{ $style['class'] }}">
                                {!! $style['icon'] !!} {{ $style['label'] }}
                            </span>
                        </td>
                        <td class="border p-2 space-x-2">
                            {{-- BUTTON EDIT --}}
                            <a href="{{ route('tasks.edit', ['task' => $task->public_slug]) }}"
                               class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold
                                      bg-green-600 hover:bg-green-700 active:bg-green-800
                                      text-white dark:text-white subpixel-antialiased drop-shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-green-400 transition-colors duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h2m2 0h.01M4 21l4-1 9-9a2 2 0 10-3-3L5 17l-1 4z"/>
                                </svg>
                                <span>Edit</span>
                            </a>

                            {{-- BUTTON HAPUS --}}
                            <form action="{{ route('tasks.destroy', $task) }}" method="POST"
                                  class="inline"
                                  onsubmit="return confirm('Hapus task ini?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="from" value="{{ request()->fullUrl() }}">
                                <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold
                                               bg-red-600 hover:bg-red-700 active:bg-red-800
                                               text-white dark:text-white subpixel-antialiased drop-shadow-sm
                                               focus:outline-none focus:ring-2 focus:ring-red-400 transition-colors duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 001 1h4a1 1 0 011 1"/>
                                    </svg>
                                    <span>Hapus</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4 text-center text-gray-500 dark:text-gray-400 transition-colors duration-300">
                            Tidak ada task in progress.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

