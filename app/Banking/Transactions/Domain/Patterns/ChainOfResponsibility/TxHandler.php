<?php

namespace App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility;

abstract class TxHandler
{
    private ?TxHandler $next = null;

    public function setNext(TxHandler $next): TxHandler
    {
        $this->next = $next;
        return $next;
    }

    protected function next(TxContext $ctx): TxContext
    {
        return $this->next ? $this->next->handle($ctx) : $ctx;
    }

    abstract public function handle(TxContext $ctx): TxContext;
}
