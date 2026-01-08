<div>
    <div class="max-w-md mx-auto mt-12 bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-6 text-center">Register</h1>

        <form wire:submit.prevent="register">
            {{-- Username --}}
            <div class="mb-4">
                <label for="username" class="block font-semibold mb-1">Username</label>
                <input type="text" id="username" wire:model.defer="username"
                    class="w-full border border-gray-300 p-2 rounded focus:ring-blue-400 focus:ring-2">
                @error('username') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- First Name --}}
            <div class="mb-4">
                <label for="first_name" class="block font-semibold mb-1">First Name</label>
                <input type="text" id="first_name" wire:model.defer="first_name"
                    class="w-full border border-gray-300 p-2 rounded focus:ring-blue-400 focus:ring-2">
                @error('first_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Last Name --}}
            <div class="mb-4">
                <label for="last_name" class="block font-semibold mb-1">Last Name</label>
                <input type="text" id="last_name" wire:model.defer="last_name"
                    class="w-full border border-gray-300 p-2 rounded focus:ring-blue-400 focus:ring-2">
                @error('last_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Email --}}
            <div class="mb-4">
                <label for="email" class="block font-semibold mb-1">Email</label>
                <input type="email" id="email" wire:model.defer="email"
                    class="w-full border border-gray-300 p-2 rounded focus:ring-blue-400 focus:ring-2">
                @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Password --}}
            <div class="mb-4">
                <label for="password" class="block font-semibold mb-1">Password</label>
                <input type="password" id="password" wire:model.defer="password"
                    class="w-full border border-gray-300 p-2 rounded focus:ring-blue-400 focus:ring-2">
                @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>

            {{-- Password Confirmation --}}
            <div class="mb-6">
                <label for="password_confirmation" class="block font-semibold mb-1">Confirm Password</label>
                <input type="password" id="password_confirmation" wire:model.defer="password_confirmation"
                    class="w-full border border-gray-300 p-2 rounded focus:ring-blue-400 focus:ring-2">
            </div>

            <button type="submit"
                class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 transition duration-150">
                Register
            </button>
        </form>

        <p class="mt-4 text-center text-sm">
            Sudah punya akun?
            <a href="{{ route('login', ['locale' => app()->getLocale() ?? config('app.locale', 'en')]) }}" class="text-blue-600 hover:underline">Login</a>
        </p>
    </div>
</div>
