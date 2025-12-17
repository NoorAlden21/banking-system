<?php

namespace App\Banking\Accounts\Application\DTOs;

final class CustomerData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
    ) {
    }
}
