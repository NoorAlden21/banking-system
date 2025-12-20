<?php

namespace App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility;

interface TxStep
{
    /** @param callable(TxContext): array $next */
    public function handle(TxContext $ctx, callable $next): array;
}
