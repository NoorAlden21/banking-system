<?php

namespace App\Banking\Accounts\Domain\Patterns\State;

final class AccountStateFactory
{
    public static function from(string $state): AccountState
    {
        return match ($state) {
            'active' => new ActiveState(),
            'frozen' => new FrozenState(),
            'suspended' => new SuspendedState(),
            'closed' => new ClosedState(),
            default => throw new \InvalidArgumentException("حالة غير معروفة: {$state}"),
        };
    }
}
