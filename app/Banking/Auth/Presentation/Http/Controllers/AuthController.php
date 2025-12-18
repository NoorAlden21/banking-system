<?php

namespace App\Banking\Auth\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Banking\Auth\Application\DTOs\LoginData;
use App\Banking\Auth\Application\DTOs\ChangePasswordData;
use App\Banking\Auth\Application\UseCases\LoginUser;
use App\Banking\Auth\Application\UseCases\LogoutUser;
use App\Banking\Auth\Application\UseCases\ChangePassword;
use App\Banking\Auth\Application\UseCases\GetMe;
use App\Banking\Auth\Presentation\Http\Requests\LoginRequest;
use App\Banking\Auth\Presentation\Http\Requests\ChangePasswordRequest;
use App\Banking\Auth\Presentation\Http\Resources\UserResource;

final class AuthController
{
    public function __construct(
        private readonly LoginUser $login,
        private readonly LogoutUser $logout,
        private readonly ChangePassword $changePassword,
        private readonly GetMe $getMe,
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $dto = new LoginData(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            deviceName: $request->filled('device_name') ? $request->string('device_name')->toString() : 'api',
        );

        $result = $this->login->handle($dto);

        return response()->json([
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
        ]);
    }

    public function me(): JsonResponse
    {
        $user = $this->getMe->handle(auth()->user());
        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->logout->handle(auth()->user());

        return response()->json([
            'message' => 'تم تسجيل الخروج',
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $dto = new ChangePasswordData(
            currentPassword: $request->string('current_password')->toString(),
            newPassword: $request->string('new_password')->toString(),
        );

        $user = $this->changePassword->handle(auth()->user(), $dto);

        return response()->json([
            'message' => 'تم تغيير كلمة المرور بنجاح',
            'user' => new UserResource($user),
        ]);
    }
}
