<?php

namespace App\Banking\Transactions\Domain\Contracts;

use App\Banking\Transactions\Domain\Entities\TransactionForPosting;
use App\Banking\Transactions\Domain\Entities\TransactionRecord;

interface TransactionRepository
{
    public function create(array $data): TransactionRecord;

    public function findByPublicId(string $publicId): ?TransactionRecord;

    public function lockForPostingByPublicId(string $publicId): ?TransactionForPosting;

    public function markPosted(int $transactionId): void;

    public function markRejected(int $transactionId): void;
}
