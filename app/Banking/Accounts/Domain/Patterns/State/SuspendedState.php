<?php

namespace App\Banking\Accounts\Domain\Patterns\State;

final class SuspendedState implements AccountState
{
    public function name(): string
    {
        return 'suspended';
    }

    public function canTransitionTo(string $targetState): bool
    {
        return in_array($targetState, ['active', 'closed'], true);
    }

    public function transitionError(string $targetState): string
    {
        return "لا يمكن الانتقال من suspended إلى {$targetState}.";
    }
}
