{{-- LEGACY (fallback) resources/views/pages/account/change-password.blade.php --}}
@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-xl">
    <h1 class="text-2xl font-bold mb-6">Change Password</h1>

    {{-- Flash messages (semua tertutup rapi) --}}
    @if (session('success'))
        <div class="mb-4 px-4 py-2 rounded bg-green-100 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 px-4 py-2 rounded bg-red-100 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('account.password.update') }}" class="space-y-4" novalidate>
        @csrf
        @method('PUT')

        <div>
            <label for="current_password" class="block text-sm font-medium mb-1">Current Password</label>
            <div class="relative">
                <input
                    id="current_password"
                    name="current_password"
                    type="password"
                    class="w-full border rounded px-3 py-2 pr-12 @error('current_password') border-red-600 @enderror"
                    autocomplete="current-password"
                    required
                >
                <button
                    type="button"
                    class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-slate-700"
                    data-toggle="current_password"
                    aria-label="Toggle password visibility"
                >
                    <svg data-eye class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1.5 12s3.5-6.5 10.5-6.5S22.5 12 22.5 12 19 18.5 12 18.5 1.5 12 1.5 12Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg data-eye-off class="h-5 w-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3l18 18" />
                        <path d="M10.58 10.58a2 2 0 0 0 2.84 2.84" />
                        <path d="M9.88 4.24A9.42 9.42 0 0 1 12 4c7 0 10.5 8 10.5 8a17.3 17.3 0 0 1-2.24 3.34" />
                        <path d="M6.35 6.35C3.55 8.13 1.5 12 1.5 12a17.05 17.05 0 0 0 7.15 7.17" />
                    </svg>
                </button>
            </div>
            @error('current_password')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium mb-1">New Password</label>
            <div class="relative">
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="w-full border rounded px-3 py-2 pr-12 @error('password') border-red-600 @enderror"
                    autocomplete="new-password"
                    required
                >
                <button
                    type="button"
                    class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-slate-700"
                    data-toggle="password"
                    aria-label="Toggle password visibility"
                >
                    <svg data-eye class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1.5 12s3.5-6.5 10.5-6.5S22.5 12 22.5 12 19 18.5 12 18.5 1.5 12 1.5 12Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg data-eye-off class="h-5 w-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3l18 18" />
                        <path d="M10.58 10.58a2 2 0 0 0 2.84 2.84" />
                        <path d="M9.88 4.24A9.42 9.42 0 0 1 12 4c7 0 10.5 8 10.5 8a17.3 17.3 0 0 1-2.24 3.34" />
                        <path d="M6.35 6.35C3.55 8.13 1.5 12 1.5 12a17.05 17.05 0 0 0 7.15 7.17" />
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium mb-1">Confirm New Password</label>
            <div class="relative">
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    class="w-full border rounded px-3 py-2 pr-12"
                    autocomplete="new-password"
                    required
                >
                <button
                    type="button"
                    class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-slate-700"
                    data-toggle="password_confirmation"
                    aria-label="Toggle password visibility"
                >
                    <svg data-eye class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1.5 12s3.5-6.5 10.5-6.5S22.5 12 22.5 12 19 18.5 12 18.5 1.5 12 1.5 12Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg data-eye-off class="h-5 w-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3l18 18" />
                        <path d="M10.58 10.58a2 2 0 0 0 2.84 2.84" />
                        <path d="M9.88 4.24A9.42 9.42 0 0 1 12 4c7 0 10.5 8 10.5 8a17.3 17.3 0 0 1-2.24 3.34" />
                        <path d="M6.35 6.35C3.55 8.13 1.5 12 1.5 12a17.05 17.05 0 0 0 7.15 7.17" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Update Password
            </button>
        </div>
    </form>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    ['current_password', 'password', 'password_confirmation'].forEach(function(id) {
      var input = document.getElementById(id);
      var toggle = document.querySelector('[data-toggle="' + id + '"]');
      if (!input || !toggle) return;

      toggle.addEventListener('click', function() {
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        var eye = toggle.querySelector('[data-eye]');
        var eyeOff = toggle.querySelector('[data-eye-off]');
        if (eye && eyeOff) {
          eye.classList.toggle('hidden', !isPassword);
          eyeOff.classList.toggle('hidden', isPassword);
        }
      });
    });
  });
</script>
@endsection
