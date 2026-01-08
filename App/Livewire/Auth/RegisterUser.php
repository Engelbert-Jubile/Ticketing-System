<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Support\UserUnitOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class RegisterUser extends Component
{
    public string $username = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $unit = '';

    public function register()
    {
        $this->validate([
            'username' => 'required|string|max:255|unique:users,username',
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8|same:password_confirmation',
            'unit' => ['required', 'string', 'max:120', Rule::in(UserUnitOptions::values())],
        ]);

        $user = User::create([
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => ($this->last_name === '' ? null : $this->last_name),
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'unit' => $this->unit,
        ]);

        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $user->assignRole($role);

        Auth::login($user);

        $locale = app()->getLocale() ?? config('app.locale', 'en');

        return redirect()->route('dashboard', ['locale' => $locale]);
    }

    public function render()
    {
        // resources/views/livewire/auth/register-user.blade.php
        return view('livewire.auth.register-user')
            ->layout('layouts.guest');
    }
}
