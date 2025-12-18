<?php

namespace App\Banking\Auth\Domain\Contracts;

use App\Models\User;

interface AuthService
{
    public function attempt(string $email, string $password): User;

    public function createToken(User $user, string $tokenName = 'api'): string;

    public function revokeCurrentToken(User $user): void;
}
