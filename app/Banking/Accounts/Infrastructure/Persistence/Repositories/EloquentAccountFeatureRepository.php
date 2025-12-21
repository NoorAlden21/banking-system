<?php

namespace App\Banking\Accounts\Infrastructure\Persistence\Repositories;

use App\Banking\Accounts\Domain\Contracts\AccountFeatureRepository;
use App\Banking\Accounts\Domain\Entities\AccountFeature;
use App\Banking\Accounts\Infrastructure\Persistence\Models\AccountFeatureModel;

final class EloquentAccountFeatureRepository implements AccountFeatureRepository
{
    public function listActiveByAccountId(int $accountId): array
    {
        $rows = AccountFeatureModel::query()
            ->where('account_id', $accountId)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[] = new AccountFeature(
                featureKey: (string) $r->feature_key,
                status: (string) $r->status,
                meta: is_array($r->meta) ? $r->meta : [],
            );
        }

        return $out;
    }

    public function upsertActive(int $accountId, string $featureKey, array $meta = []): void
    {
        AccountFeatureModel::query()->updateOrCreate(
            ['account_id' => $accountId, 'feature_key' => $featureKey],
            ['status' => 'active', 'meta' => $meta]
        );
    }

    public function disable(int $accountId, string $featureKey): void
    {
        AccountFeatureModel::query()
            ->where('account_id', $accountId)
            ->where('feature_key', $featureKey)
            ->update(['status' => 'disabled']);
    }
}
