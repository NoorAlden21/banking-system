<?php

namespace App\Banking\Transactions\Domain\Contracts;

interface TransactionReadRepository
{
    /** @return array{items: array, meta: array} */
    public function paginateForUser(int $userId, array $filters, int $perPage, int $page): array;

    /** @return array{items: array, meta: array} */
    public function paginateAll(array $filters, int $perPage, int $page): array;

    public function findDetailForUser(string $publicId, int $userId): ?array;

    public function findDetail(string $publicId): ?array;

    public function sumPostedOutflowForAccount(int $sourceAccountId, string $from, string $to): string;
}
