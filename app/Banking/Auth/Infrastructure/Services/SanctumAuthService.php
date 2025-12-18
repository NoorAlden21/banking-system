<?php

namespace App\Banking\Auth\Infrastructure\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Banking\Auth\Domain\Contracts\AuthService;
use App\Banking\Auth\Domain\Exceptions\InvalidCredentials;

final class SanctumAuthService implements AuthService
{
    public function attempt(string $email, string $password): User
    {
        $user = User::query()->where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new InvalidCredentials('بيانات الدخول غير صحيحة');
        }

        return $user;
    }

    public function createToken(User $user, string $tokenName = 'api'): string
    {
        return $user->createToken($tokenName)->plainTextToken;
    }

    public function revokeCurrentToken(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
