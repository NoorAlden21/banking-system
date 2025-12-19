<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Gateways;

use App\Banking\Transactions\Domain\Contracts\AccountGateway;
use App\Banking\Transactions\Domain\Contracts\LockedAccount;
use App\Banking\Accounts\Infrastructure\Persistence\Models\AccountModel;

final class EloquentAccountGateway implements AccountGateway
{
    public function lockByPublicIdsForUpdate(array $publicIds): array
    {
        $ids = array_values(array_unique(array_filter($publicIds)));
        sort($ids); // مهم لتجنب deadlocks

        $models = AccountModel::query()
            ->whereIn('public_id', $ids)
            ->lockForUpdate()
            ->get();

        $map = [];
        foreach ($models as $m) {
            $map[(string) $m->public_id] = new LockedAccount(
                id: (int) $m->id,
                publicId: (string) $m->public_id,
                parentId: $m->parent_id ? (int) $m->parent_id : null,
                type: (string) $m->type,
                state: (string) $m->state,
                balance: (string) $m->balance,
            );
        }
        return $map;
    }

    public function lockByIdForUpdate(int $id): ?LockedAccount
    {
        $m = AccountModel::query()->where('id', $id)->lockForUpdate()->first();
        if (!$m) return null;

        return new LockedAccount(
            id: (int) $m->id,
            publicId: (string) $m->public_id,
            parentId: $m->parent_id ? (int) $m->parent_id : null,
            type: (string) $m->type,
            state: (string) $m->state,
            balance: (string) $m->balance,
        );
    }

    public function updateBalance(int $accountId, string $newBalance): void
    {
        AccountModel::query()->where('id', $accountId)->update(['balance' => $newBalance]);
    }
}
