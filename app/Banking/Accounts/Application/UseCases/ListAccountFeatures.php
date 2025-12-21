<?php

namespace App\Banking\Accounts\Application\UseCases;

use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Domain\Contracts\AccountFeatureRepository;

final class ListAccountFeatures
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly AccountFeatureRepository $features,
    ) {
    }

    public function handle(int $actorUserId, bool $canViewAll, string $accountPublicId): array
    {
        $account = $this->accounts->findByPublicId($accountPublicId);
        if (!$account) throw new \RuntimeException('الحساب غير موجود');

        if (!$canViewAll && (int) $account->userId !== $actorUserId) {
            throw new \RuntimeException('غير مسموح');
        }

        $items = $this->features->listActiveByAccountId((int) $account->id);

        return array_map(fn ($f) => [
            'feature_key' => $f->featureKey,
            'meta' => $f->meta,
        ], $items);
    }
}
