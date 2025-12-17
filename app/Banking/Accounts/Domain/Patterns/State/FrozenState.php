<?php

namespace App\Banking\Accounts\Domain\Patterns\State;

final class FrozenState implements AccountState
{
    public function name(): string
    {
        return 'frozen';
    }

    public function canTransitionTo(string $targetState): bool
    {
        return in_array($targetState, ['active', 'suspended', 'closed'], true);
    }

    public function transitionError(string $targetState): string
    {
        return "لا يمكن الانتقال من frozen إلى {$targetState}.";
    }
}
