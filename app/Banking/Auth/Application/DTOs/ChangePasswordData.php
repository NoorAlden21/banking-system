<?php

namespace App\Banking\Auth\Application\DTOs;

final class ChangePasswordData
{
    public function __construct(
        public readonly string $currentPassword,
        public readonly string $newPassword,
    ) {
    }
}
