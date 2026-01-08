{{-- LEGACY (fallback) resources/views/pages/user/show.blade.php --}}
@extends('layouts.app')

@section('title', 'User Detail')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- Kembali: HANYA ke ?from (Report). Jika kosong, fallback ke route users.report --}}
    @include('components.back', ['to' => request('from') ?: route('users.report')])

    <div class="max-w-3xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold">User Detail</h2>

            @can('update', $user)
                {{-- Edit dari DETAIL: beri penanda src=detail + pertahankan ?from --}}
                <a href="{{ route('users.edit', ['user' => $user->id, 'from' => request('from'), 'src' => 'detail']) }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Edit
                </a>
            @endcan
        </div>

        <div class="bg-white dark:bg-gray-800 rounded shadow p-6 space-y-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Name</p>
                <p class="font-medium">
                    {{ $user->first_name }}{{ $user->last_name ? ' ' . $user->last_name : '' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                <p class="font-medium">{{ $user->email }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Role</p>
                <p class="font-medium">{{ $user->getRoleNames()->join(', ') }}</p>
            </div>

            @can('delete', $user)
                <div class="pt-2">
                    <form action="{{ route('users.destroy', $user) }}" method="POST"
                          onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                            Hapus
                        </button>
                    </form>
                </div>
            @endcan
        </div>

    </div>
</div>
@endsection
