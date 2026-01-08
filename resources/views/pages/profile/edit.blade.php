@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="container">
    <h2 class="text-xl font-semibold mb-4">My Profile</h2>

    @if(session('success'))
        <div class="text-green-600 mb-4">{{ session('success') }}</div>
    @endif

    <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PATCH')

        {{-- Username --}}
        <div>
            <label for="username" class="block text-sm font-medium">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                value="{{ old('username', $user->username) }}"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                required
            >
            @error('username')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- First Name --}}
        <div>
            <label for="first_name" class="block text-sm font-medium">First Name</label>
            <input
                type="text"
                id="first_name"
                name="first_name"
                value="{{ old('first_name', $user->first_name) }}"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                required
            >
            @error('first_name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Last Name --}}
        <div>
            <label for="last_name" class="block text-sm font-medium">Last Name</label>
            <input
                type="text"
                id="last_name"
                name="last_name"
                value="{{ old('last_name', $user->last_name) }}"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
            >
            @error('last_name')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email', $user->email) }}"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                required
            >
            @error('email')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded"
            >
                Update Profile
            </button>

            <form
                action="{{ route('profile.destroy') }}"
                method="POST"
                onsubmit="return confirm('Are you sure you want to delete your account?');"
            >
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="text-red-600 hover:underline"
                >
                    Delete Account
                </button>
            </form>
        </div>
    </form>
</div>
@endsection
