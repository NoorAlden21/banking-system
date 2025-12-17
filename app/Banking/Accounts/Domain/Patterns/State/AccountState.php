<?php

namespace App\Banking\Accounts\Domain\Patterns\State;

interface AccountState
{
    /** اسم الحالة في DB */
    public function name(): string;

    /** هل مسموح الانتقال للحالة المطلوبة؟ */
    public function canTransitionTo(string $targetState): bool;

    /** رسالة خطأ لطيفة لو الانتقال غير مسموح */
    public function transitionError(string $targetState): string;
}
