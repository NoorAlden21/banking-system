<?php

namespace App\Banking\Transactions\Domain\Contracts;

use App\Banking\Transactions\Domain\Entities\TransactionRecord;

interface TransactionRepository
{
    public function create(array $data): TransactionRecord;

    public function findByPublicId(string $publicId): ?TransactionRecord;
}
