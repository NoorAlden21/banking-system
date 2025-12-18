<?php

namespace App\Banking\Auth\Application\UseCases;

use App\Models\User;

final class GetMe
{
    public function handle(User $user): User
    {
        return $user;
    }
}
