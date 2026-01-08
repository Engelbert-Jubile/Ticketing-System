<?php

namespace App\Domains\User\UseCases;

use App\Domains\User\DTO\UserData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUser
{
    public function execute(UserData $data): User
    {
        return User::create([
            'username' => $data->username,
            'first_name' => $data->first_name,
            'last_name' => $data->last_name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);
    }
}
