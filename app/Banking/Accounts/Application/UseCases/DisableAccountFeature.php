<?php

namespace App\Banking\Accounts\Application\UseCases;

use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Domain\Contracts\AccountFeatureRepository;
use Illuminate\Support\Facades\DB;

final class DisableAccountFeature
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly AccountFeatureRepository $features,
    ) {
    }

    public function handle(int $actorUserId, bool $canManageAny, string $accountPublicId, string $featureKey): array
    {
        return DB::transaction(function () use ($actorUserId, $canManageAny, $accountPublicId, $featureKey) {

            $account = $this->accounts->findByPublicId($accountPublicId);
            if (!$account) throw new \RuntimeException('الحساب غير موجود');

            if (!$canManageAny && (int) $account->userId !== $actorUserId) {
                throw new \RuntimeException('غير مسموح');
            }

            $this->features->disable((int) $account->id, $featureKey);

            return [
                'message' => 'تم تعطيل الـfeature',
                'account_public_id' => $accountPublicId,
                'feature_key' => $featureKey,
            ];
        });
    }
}
