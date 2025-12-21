<?php

namespace App\Banking\Accounts\Application\UseCases;

use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Domain\Contracts\AccountFeatureRepository;
use Illuminate\Support\Facades\DB;

final class EnableAccountFeature
{
    private const ALLOWED = ['overdraft', 'premium', 'insurance'];

    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly AccountFeatureRepository $features,
    ) {
    }

    public function handle(int $actorUserId, bool $canManageAny, string $accountPublicId, string $featureKey, array $meta): array
    {
        if (!in_array($featureKey, self::ALLOWED, true)) {
            throw new \RuntimeException('Feature غير مدعوم');
        }

        return DB::transaction(function () use ($actorUserId, $canManageAny, $accountPublicId, $featureKey, $meta) {

            $account = $this->accounts->findByPublicId($accountPublicId);
            if (!$account) throw new \RuntimeException('الحساب غير موجود');

            if ($account->isGroup()) throw new \RuntimeException('لا يمكن إضافة features لحساب group');

            if (!$canManageAny) {
                throw new \RuntimeException('غير مسموح');
            }

            $this->features->upsertActive((int) $account->id, $featureKey, $meta);

            return [
                'message' => 'تم تفعيل الـfeature',
                'account_public_id' => $accountPublicId,
                'feature_key' => $featureKey,
                'meta' => $meta,
            ];
        });
    }
}
