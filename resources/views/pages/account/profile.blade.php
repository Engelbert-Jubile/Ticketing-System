{{-- LEGACY (fallback) resources/views/pages/account/profile.blade.php --}}
@extends('layouts.app')

@section('title', 'Profile')

@section('content')
@php
  $me = auth()->user();
@endphp
<div class="container mx-auto px-4 py-6 max-w-2xl">
    <h1 class="text-2xl font-bold mb-6">Profile</h1>

    @if(session('success'))
        <div class="mb-4 px-4 py-2 rounded bg-green-100 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('account.update-profile') }}" class="space-y-5" novalidate>
        @csrf
        @method('PUT')

        {{-- First / Last: berdampingan di desktop, stack di mobile --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="first_name" class="block text-sm font-medium mb-1">First Name</label>
                <input
                    id="first_name"
                    name="first_name"
                    type="text"
                    class="w-full border rounded px-3 py-2 @error('first_name') border-red-600 @enderror"
                    value="{{ old('first_name', $me->first_name) }}"
                    autocomplete="given-name"
                    required
                >
                @error('first_name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="last_name" class="block text-sm font-medium mb-1">Last Name</label>
                <input
                    id="last_name"
                    name="last_name"
                    type="text"
                    class="w-full border rounded px-3 py-2 @error('last_name') border-red-600 @enderror"
                    value="{{ old('last_name', $me->last_name) }}"
                    autocomplete="family-name"
                >
                @error('last_name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="email" class="block text-sm font-medium mb-1">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                class="w-full border rounded px-3 py-2 @error('email') border-red-600 @enderror"
                value="{{ old('email', $me->email) }}"
                autocomplete="email"
                required
            >
            @error('email')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
