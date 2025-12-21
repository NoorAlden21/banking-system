<?php

namespace App\Banking\Accounts\Domain\Contracts;

use App\Banking\Accounts\Domain\Entities\AccountFeature;

interface AccountFeatureRepository
{
    /** @return array<int, AccountFeature> */
    public function listActiveByAccountId(int $accountId): array;

    public function upsertActive(int $accountId, string $featureKey, array $meta = []): void;

    public function disable(int $accountId, string $featureKey): void;
}
