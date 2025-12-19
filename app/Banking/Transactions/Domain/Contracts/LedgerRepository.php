<?php

namespace App\Banking\Transactions\Domain\Contracts;

interface LedgerRepository
{
    public function create(array $data): void;
}
