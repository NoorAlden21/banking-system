<?php

namespace App\Banking\Accounts\Application\DTOs;

final class ChangeStateData
{
    public function __construct(
        public readonly string $targetState
    ) {
    }
}
