<?php

namespace App\Domains\User\UseCases;

use App\Models\User;

class GetAllUsers
{
    public function execute(): \Illuminate\Support\Collection
    {
        return User::latest()->get();
    }
}
