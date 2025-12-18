<?php

namespace App\Banking\Auth\Application\UseCases;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Banking\Auth\Application\DTOs\ChangePasswordData;
use App\Banking\Auth\Domain\Exceptions\InvalidCredentials;

final class ChangePassword
{
    public function handle(User $user, ChangePasswordData $data): User
    {
        if (!Hash::check($data->currentPassword, $user->password)) {
            throw new InvalidCredentials('كلمة المرور الحالية غير صحيحة');
        }

        $user->password = Hash::make($data->newPassword);
        $user->must_change_password = false;
        $user->password_changed_at = now();
        $user->save();

        return $user;
    }
}
