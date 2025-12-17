<?php

namespace App\Banking\Accounts\Domain\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final class CustomerOnboarded implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly int $userId,
        public readonly string $userPublicId,
        public readonly string $email,
        public readonly string $plainPassword,
    ) {
    }
}
