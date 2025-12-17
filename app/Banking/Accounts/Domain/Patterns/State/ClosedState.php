<?php

namespace App\Banking\Accounts\Domain\Patterns\State;

final class ClosedState implements AccountState
{
    public function name(): string
    {
        return 'closed';
    }

    public function canTransitionTo(string $targetState): bool
    {
        return false;
    }

    public function transitionError(string $targetState): string
    {
        return "الحساب مغلق نهائيًا ولا يمكن تغييره إلى {$targetState}.";
    }
}
