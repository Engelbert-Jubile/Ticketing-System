@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-10 px-4">
    <h2 class="text-2xl font-bold mb-6">Edit Profile</h2>

    @if (session('status'))
        <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <div>
            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
            <input type="text" name="username" id="username" value="{{ old('username', auth()->user()->username) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @error('username') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', auth()->user()->first_name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @error('first_name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', auth()->user()->last_name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @error('last_name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', auth()->user()->email) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @error('email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-between items-center mt-6">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Save Changes
            </button>

            <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Are you sure you want to delete your account?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600 hover:underline">
                    Delete Account
                </button>
            </form>
        </div>
    </form>
</div>
@endsection

