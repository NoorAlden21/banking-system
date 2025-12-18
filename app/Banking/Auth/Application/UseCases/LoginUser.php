<?php

namespace App\Banking\Auth\Application\UseCases;

use App\Banking\Auth\Application\DTOs\LoginData;
use App\Banking\Auth\Domain\Contracts\AuthService;

final class LoginUser
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function handle(LoginData $data): array
    {
        $user = $this->auth->attempt($data->email, $data->password);

        $token = $this->auth->createToken($user, $data->deviceName);

        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
