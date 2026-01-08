<?php

namespace App\Domains\User\DTO;

use Illuminate\Http\Request;

class UserData
{
    public string $username;

    public string $first_name;

    public ?string $last_name;

    public string $email;

    public string $password;

    public function __construct(string $username, string $first_name, ?string $last_name, string $email, string $password)
    {
        $this->username = $username;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->email = $email;
        $this->password = $password;
    }

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'first_name' => ['required', 'string'],
            'last_name' => ['nullable', 'string'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8'],
        ]);

        return new self(
            $validated['username'],
            $validated['first_name'],
            $validated['last_name'] ?? null,
            $validated['email'],
            $validated['password']
        );
    }
}
