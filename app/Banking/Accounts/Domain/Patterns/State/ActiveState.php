<?php

namespace App\Banking\Accounts\Domain\Patterns\State;

final class ActiveState implements AccountState
{
    public function name(): string
    {
        return 'active';
    }

    public function canTransitionTo(string $targetState): bool
    {
        return in_array($targetState, ['frozen', 'suspended', 'closed'], true);
    }

    public function transitionError(string $targetState): string
    {
        return "لا يمكن الانتقال من active إلى {$targetState}.";
    }
}
