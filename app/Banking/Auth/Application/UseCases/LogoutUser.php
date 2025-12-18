<?php

namespace App\Banking\Auth\Application\UseCases;

use App\Models\User;
use App\Banking\Auth\Domain\Contracts\AuthService;

final class LogoutUser
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function handle(User $user): void
    {
        $this->auth->revokeCurrentToken($user);
    }
}
