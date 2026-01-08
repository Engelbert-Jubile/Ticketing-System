{{-- LEGACY (fallback) resources/views/pages/user/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-lg">

    @php
        $originFrom = request('from'); // URL report jika ada
        $backTo = request('src') === 'detail'
            ? route('users.show', ['user' => $user->id, 'from' => $originFrom])
            : ($originFrom ?: route('users.report'));

        $currentRole   = old('role', $user->roles->pluck('name')->first() ?? $user->role);
        $isSuperAdmin  = auth()->user()?->hasRole('super-admin');
        // $roles dikirim dari controller:
        //  - super-admin: ['admin','user','super-admin']
        //  - admin:       ['admin','user']
        // Sudah dibentuk sebagai ["value" => "Label"]
    @endphp

    {{-- Tombol Kembali --}}
    @include('components.back', ['to' => $backTo])

    <h1 class="text-2xl font-bold mb-4">Edit User</h1>

    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')

        {{-- Balik ke Report setelah update (jika ada) --}}
        @if(!empty($originFrom))
            <input type="hidden" name="from" value="{{ $originFrom }}">
        @endif

        {{-- Username --}}
        <div class="mb-4">
            <label for="username" class="block font-medium mb-1">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                value="{{ old('username', $user->username) }}"
                class="w-full border rounded px-3 py-2"
                required
            >
            @error('username')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- First Name --}}
        <div class="mb-4">
            <label for="first_name" class="block font-medium mb-1">First Name</label>
            <input
                type="text"
                id="first_name"
                name="first_name"
                value="{{ old('first_name', $user->first_name) }}"
                class="w-full border rounded px-3 py-2"
                required
            >
            @error('first_name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Last Name --}}
        <div class="mb-4">
            <label for="last_name" class="block font-medium mb-1">Last Name (opsional)</label>
            <input
                type="text"
                id="last_name"
                name="last_name"
                value="{{ old('last_name', $user->last_name) }}"
                class="w-full border rounded px-3 py-2"
            >
            @error('last_name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div class="mb-4">
            <label for="email" class="block font-medium mb-1">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email', $user->email) }}"
                class="w-full border rounded px-3 py-2"
                required
            >
            @error('email')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Role (dropdown dinamis sesuai role login) --}}
        <div class="mb-6">
            <label for="role" class="block font-medium mb-1">Role</label>

            <select
                id="role"
                name="role"
                class="w-full border rounded px-3 py-2"
                required
            >
                @foreach($roles as $role)
                    <option value="{{ $role['value'] }}" {{ $currentRole === $role['value'] ? 'selected' : '' }}>
                        {{ $role['label'] }}
                    </option>
                @endforeach
            </select>

            {{-- Info kecil untuk membedakan tampilan admin vs super-admin --}}
            <p class="text-xs text-gray-500 mt-1">
                @if($isSuperAdmin)
                    Anda login sebagai <strong>super-admin</strong>: opsi <em>User</em>, <em>Admin</em>, dan <em>Super-Admin</em> tersedia.
                @else
                    Anda login sebagai <strong>admin</strong>: hanya <em>User</em> dan <em>Admin</em> yang tersedia.
                @endif
            </p>

            @error('role')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            Update User
        </button>
    </form>
</div>
@endsection
