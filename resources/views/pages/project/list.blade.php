{{-- resources/views/pages/project/list.blade.php --}}
@extends('layouts.app')
@section('title', $title ?? 'Daftar Project')

@section('content')
<div class="container mx-auto px-4 py-6">
    @isset($showBackTo)
    <a href="{{ $showBackTo['url'] ?? route('dashboard') }}"
        class="inline-flex items-center gap-2 px-3 py-2 rounded font-semibold !text-white {{ $showBackTo['color'] ?? 'bg-gray-500' }} hover:opacity-90">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4A1 1 0 018.707 6.293L6.414 8.586H17a1 1 0 110 2H6.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
        </svg>
        Kembali
    </a>
    @endisset

    <h1 class="text-2xl font-bold mt-4 mb-4">{{ $heading ?? 'Daftar Project' }}</h1>

    @if(!empty($showCreateBtn))
    <div class="mb-4">
        <a href="{{ route('projects.create') }}"
            class="inline-flex items-center px-4 py-2 rounded font-semibold bg-blue-600 !text-white hover:bg-blue-700">
            + Buat Project Baru
        </a>
    </div>
    @endif

    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
        <table class="min-w-full text-sm table-fixed border-collapse">
            <colgroup>
                <col class="w-14" /> {{-- # --}}
                <col class="min-w-0" /> {{-- Title --}}
                <col class="w-44" /> {{-- Status --}}
                <col class="w-72" /> {{-- Aksi --}}
            </colgroup>

            <thead class="table-head-silver">
                <tr>
                    <th class="px-6 py-2 text-left align-middle">#</th>
                    <th class="px-6 py-2 text-left align-middle">Title</th>
                    <th class="px-6 py-2 text-left align-middle">Status</th>
                    @if(!empty($showAction))
                    <th class="px-6 py-2 text-left align-middle">Aksi</th>
                    @endif
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($projects as $p)
                @php
                $statusKey = \Illuminate\Support\Str::of($p->status)->snake();
                $statusLabel = \Illuminate\Support\Str::of($p->status)->replace('_',' ')->title();

                $isProgress = \Illuminate\Support\Str::contains((string) $statusKey, 'progress');
                if ($isProgress) {
                $badgeIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16Zm.75-12a.75.75 0 00-1.5 0v4c0 .199.079.39.22.53l2.5 2.5a.75.75 0 101.06-1.06l-2.28-2.28V6z" clip-rule="evenodd" />
                </svg>';
                } elseif ((string) $statusKey === 'completed' || (string) $statusKey === 'done') {
                $badgeIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16Zm3.707-9.707a1 1 0 00-1.414-1.414L9 10.879 7.707 9.586A1 1 0 006.293 11l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>';
                } else {
                $badgeIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.802 2.036a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.802-2.036a1 1 0 00-1.175 0L6.89 16.283c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L3.255 8.72c-.783-.57-.38-1.81.588-1.81h3.462a1 1 0 00.95-.69l1.07-3.292z" />
                </svg>';
                }
                @endphp

                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/20">
                    <td class="px-6 py-2 align-middle tabular-nums">{{ $loop->iteration }}</td>
                    <td class="px-6 py-2 align-middle font-medium min-w-0">
                        <span class="block truncate">{{ $p->title }}</span>
                    </td>

                    <td class="px-6 py-2 align-middle">
                        <span class="status-badge whitespace-nowrap" data-status="{{ $statusKey }}">
                            {!! $badgeIcon !!} {{ $statusLabel }}
                        </span>
                    </td>

                    @if(!empty($showAction))
                    <td class="px-6 py-2 align-middle">
                        <div class="flex flex-wrap items-center gap-2 whitespace-nowrap">
                            {{-- View --}}
                            <a href="{{ route('projects.show', ['project'=>$p->public_slug ?? $p->id, 'from'=>request()->fullUrl()]) }}"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium
                                          bg-sky-100 text-sky-800 ring-1 ring-sky-300 hover:bg-sky-200
                                          dark:bg-sky-300 dark:!text-gray-900 dark:ring-sky-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 4.5c-5 0-8 5.5-8 5.5s3 5.5 8 5.5 8-5.5 8-5.5-3-5.5-8-5.5Zm0 9a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7Z" />
                                </svg>
                                View
                            </a>

                            {{-- Edit --}}
                            <a href="{{ route('projects.edit', ['project'=>$p->public_slug ?? $p->id, 'from'=>request()->fullUrl()]) }}"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium
                                          bg-green-100 text-green-800 ring-1 ring-green-300 hover:bg-green-200
                                          dark:bg-green-300 dark:!text-gray-900 dark:ring-green-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 0 1 2.828 2.828l-8.5 8.5a2 2 0 0 1-.878.515l-3.036.76a.5.5 0 0 1-.606-.606l.76-3.036a2 2 0 0 1 .515-.878l8.5-8.5Z" />
                                </svg>
                                Edit
                            </a>

                            {{-- Delete --}}
                            <form action="{{ route('projects.destroy',$p) }}" method="POST" class="inline" onsubmit="return confirm('Hapus?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="from" value="{{ request()->fullUrl() }}">
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium
                                                   bg-rose-100 text-rose-800 ring-1 ring-rose-300 hover:bg-rose-200
                                                   dark:bg-rose-300 dark:text-gray-900 dark:ring-rose-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-1 1v1H5.5a.75.75 0 000 1.5h9a.75.75 0 000-1.5H12V3a1 1 0 00-1-1H9Zm-3 5.5a.75.75 0 01.75-.75h6.5a.75.75 0 01.75.75v7A2.5 2.5 0 0111.5 17h-3A2.5 2.5 0 015 14.5v-7Z" clip-rule="evenodd" />
                                    </svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ !empty($showAction) ? 4 : 3 }}" class="px-6 py-6 text-center text-gray-500">
                        Tidak ada data.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection