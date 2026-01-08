{{-- LEGACY (fallback) resources/views/pages/user/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div class="container">

   {{-- Tombol kembali ke Dashboard --}}
    @include('components.back', ['to' => route('dashboard')])

    <h2 class="text-xl font-semibold mb-4">Create User</h2>

    <form action="{{ route('users.store') }}" method="POST">
        @csrf

        {{-- Username --}}
        <div class="mb-4">
            <label for="username" class="block text-sm font-medium">Username</label>
            <input
                type="text"
                name="username"
                id="username"
                class="form-input w-full"
                required
                value="{{ old('username') }}"
            >
            @error('username')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- First Name --}}
        <div class="mb-4">
            <label for="first_name" class="block text-sm font-medium">First Name</label>
            <input
                type="text"
                name="first_name"
                id="first_name"
                class="form-input w-full"
                required
                value="{{ old('first_name') }}"
            >
            @error('first_name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Last Name --}}
        <div class="mb-4">
            <label for="last_name" class="block text-sm font-medium">Last Name (opsional)</label>
            <input
                type="text"
                name="last_name"
                id="last_name"
                class="form-input w-full"
                value="{{ old('last_name') }}"
            >
            @error('last_name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium">Email</label>
            <input
                type="email"
                name="email"
                id="email"
                class="form-input w-full"
                required
                value="{{ old('email') }}"
            >
            @error('email')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium">Password</label>
            <input
                type="password"
                name="password"
                id="password"
                class="form-input w-full"
                required
            >
            @error('password')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password Confirmation --}}
        <div class="mb-4">
            <label for="password_confirmation" class="block text-sm font-medium">Confirm Password</label>
            <input
                type="password"
                name="password_confirmation"
                id="password_confirmation"
                class="form-input w-full"
                required
            >
        </div>

        {{-- Role --}}
        <div class="mb-6">
            <label for="role" class="block text-sm font-medium">Role</label>

            {{-- Pastikan select native (tanpa size/multiple) --}}
            <select
                name="role"
                id="role"
                class="form-select w-full"
                required
            >
                <option value="" disabled {{ old('role') ? '' : 'selected' }}>Pilih Role</option>

                {{-- $roles dikirim dari controller sebagai array key=>label --}}
                @if(isset($roles) && is_array($roles))
                    @foreach ($roles as $role)
                        <option value="{{ $role['value'] }}" {{ old('role') === $role['value'] ? 'selected' : '' }}>
                            {{ $role['label'] }}
                        </option>
                    @endforeach
                @else
                    {{-- fallback --}}
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="user"  {{ old('role') === 'user'  ? 'selected' : '' }}>User</option>
                @endif
            </select>

            @error('role')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit --}}
        <div class="text-right">
            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded"
            >
                Save User
            </button>
        </div>
    </form>
</div>
@endsection
