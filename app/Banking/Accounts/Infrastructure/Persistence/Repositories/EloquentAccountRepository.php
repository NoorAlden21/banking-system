<?php

namespace App\Banking\Accounts\Infrastructure\Persistence\Repositories;

use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Domain\Entities\Account;
use App\Banking\Accounts\Domain\Enums\AccountStateEnum;
use App\Banking\Accounts\Domain\Enums\AccountTypeEnum;
use App\Banking\Accounts\Infrastructure\Persistence\Models\AccountModel;

class EloquentAccountRepository implements AccountRepository
{
    public function findUserGroup(int $userId): ?Account
    {
        $model = AccountModel::query()
            ->where('user_id', $userId)
            ->where('type', AccountTypeEnum::GROUP->value)
            ->whereNull('parent_id')
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function createGroup(int $userId): Account
    {
        $model = AccountModel::query()->create([
            'user_id' => $userId,
            'parent_id' => null,
            'type' => AccountTypeEnum::GROUP->value,
            'state' => AccountStateEnum::ACTIVE->value,
            'balance' => '0.00',
        ]);

        return $this->toEntity($model);
    }

    public function createChildAccount(
        int $userId,
        int $groupId,
        AccountTypeEnum $type,
        ?string $dailyLimit,
        ?string $monthlyLimit
    ): Account {
        $model = AccountModel::query()->create([
            'user_id' => $userId,
            'parent_id' => $groupId,
            'type' => $type->value,
            'state' => AccountStateEnum::ACTIVE->value,
            'balance' => '0.00',
            'daily_limit' => $dailyLimit,
            'monthly_limit' => $monthlyLimit,
        ]);

        return $this->toEntity($model);
    }

    public function listByUser(int $userId): array
    {
        return AccountModel::query()
            ->where('user_id', $userId)
            ->orderByRaw("type = 'group' DESC") // group أولاً
            ->orderBy('id')
            ->get()
            ->map(fn (AccountModel $m) => $this->toEntity($m))
            ->all();
    }

    public function findByPublicId(string $publicId): ?Account
    {
        $model = AccountModel::query()->where('public_id', $publicId)->first();
        return $model ? $this->toEntity($model) : null;
    }

    private function toEntity(AccountModel $m): Account
    {
        return new Account(
            id: (int) $m->id,
            publicId: (string) $m->public_id,
            userId: (int) $m->user_id,
            parentId: $m->parent_id ? (int) $m->parent_id : null,
            type: AccountTypeEnum::from((string) $m->type),
            state: AccountStateEnum::from((string) $m->state),
            balance: (string) $m->balance,
            dailyLimit: $m->daily_limit !== null ? (string) $m->daily_limit : null,
            monthlyLimit: $m->monthly_limit !== null ? (string) $m->monthly_limit : null,
            closedAt: $m->closed_at?->toISOString(),
            createdAt: $m->created_at?->toISOString() ?? now()->toISOString(),
            updatedAt: $m->updated_at?->toISOString() ?? now()->toISOString(),
        );
    }
}
