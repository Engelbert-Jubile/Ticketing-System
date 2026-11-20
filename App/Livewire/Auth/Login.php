<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    protected string $layout = 'layouts.guest';

    protected array $layoutData = ['title' => 'Login'];

    public function login()
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = strtolower(trim($this->email));
        if (Auth::attempt(['email' => $email, 'password' => $this->password], remember: true)) {
            request()->session()->regenerate();
            request()->session()->forget('url.intended');

            return $this->redirect(portalEntryUrl(app()->getLocale()));
        }

        $this->addError('email', 'Email atau password salah.');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
