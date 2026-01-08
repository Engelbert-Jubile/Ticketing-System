{{-- resources/views/auth/register.blade.php --}}
@extends('layouts.guest')

@section('title', 'Register')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 p-4">
    <div class="w-full max-w-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-6 text-center">Create a New Account</h1>

        {{-- Livewire form --}}
        @livewire('auth.register-user')

        <p class="mt-4 text-center text-sm text-gray-700 dark:text-gray-300">
            Already have an account?
            @php $currentLocale = app()->getLocale() ?? config('app.locale', 'en'); @endphp
            <a href="{{ route('login', ['locale' => $currentLocale]) }}"
               class="text-blue-600 dark:text-blue-400 hover:underline">
               Login here
            </a>
        </p>
    </div>
</div>
@endsection
