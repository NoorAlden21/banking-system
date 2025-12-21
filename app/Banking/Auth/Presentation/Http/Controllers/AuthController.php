<?php

namespace App\Banking\Auth\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;

use App\Models\User;

use App\Banking\Shared\Domain\Contracts\AuditLogger;
use App\Banking\Shared\Application\DTOs\AuditEntryData;

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
        private readonly AuditLogger $audit,
    ) {
    }

    private function actorRole(?User $user): string
    {
        if (!$user) return 'guest';
        if (method_exists($user, 'getRoleNames')) {
            return (string) ($user->getRoleNames()->first() ?? 'user');
        }
        return 'user';
    }

    private function safeAudit(?User $actor, string $action, ?string $subjectType = null, ?string $subjectPublicId = null, array $meta = []): void
    {
        try {
            $req = request();
            $this->audit->log(new AuditEntryData(
                actorUserId: (int) ($actor?->id ?? 0),
                actorRole: $this->actorRole($actor),
                action: $action,
                subjectType: $subjectType,
                subjectPublicId: $subjectPublicId,
                ip: $req?->ip(),
                userAgent: (string) ($req?->userAgent() ?? ''),
                meta: $meta,
            ));
        } catch (\Throwable $ignored) {
        }
    }

    private function errorMeta(\Throwable $e): array
    {
        return [
            'error' => class_basename($e),
            'message' => $e->getMessage(),
        ];
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $email = $request->string('email')->toString();

        $this->safeAudit(null, 'auth.login.attempt', 'auth', null, [
            'email' => $email,
            'device' => $request->filled('device_name') ? $request->string('device_name')->toString() : 'api',
        ]);

        $dto = new LoginData(
            email: $email,
            password: $request->string('password')->toString(), // ⚠️ never logged
            deviceName: $request->filled('device_name') ? $request->string('device_name')->toString() : 'api',
        );

        try {
            $result = $this->login->handle($dto);

            /** @var User $user */
            $user = $result['user'];

            $this->safeAudit($user, 'auth.login.success', 'user', (string) ($user->public_id ?? ''), [
                'email' => (string) $user->email,
            ]);

            return response()->json([
                'token' => $result['token'],
                'user' => new UserResource($user),
            ]);
        } catch (\Throwable $e) {
            $this->safeAudit(null, 'auth.login.failed', 'auth', null, array_merge([
                'email' => $email,
            ], $this->errorMeta($e)));

            throw $e;
        }
    }

    public function me(): JsonResponse
    {
        $user = $this->getMe->handle(auth()->user());
        return response()->json(['user' => new UserResource($user)]);
    }

    public function logout(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->logout->handle($user);

        $this->safeAudit($user, 'auth.logout', 'user', (string) ($user->public_id ?? ''));

        return response()->json(['message' => 'تم تسجيل الخروج']);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->safeAudit($user, 'auth.change_password.attempt', 'user', (string) ($user->public_id ?? ''));

        $dto = new ChangePasswordData(
            currentPassword: $request->string('current_password')->toString(),
            newPassword: $request->string('new_password')->toString(),
        );

        try {
            $updatedUser = $this->changePassword->handle($user, $dto);

            $this->safeAudit($user, 'auth.change_password.success', 'user', (string) ($updatedUser->public_id ?? ''));

            return response()->json([
                'message' => 'تم تغيير كلمة المرور بنجاح',
                'user' => new UserResource($updatedUser),
            ]);
        } catch (\Throwable $e) {
            $this->safeAudit($user, 'auth.change_password.failed', 'user', (string) ($user->public_id ?? ''), $this->errorMeta($e));
            throw $e;
        }
    }
}
