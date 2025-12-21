<?php

namespace App\Banking\Accounts\Application\UseCases;

use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Domain\Contracts\AccountFeatureRepository;
use App\Banking\Accounts\Domain\Patterns\Decorator\BaseAccountComponent;
use App\Banking\Accounts\Domain\Services\AccountFeatureDecoratorBuilder;

final class GetAccountCapabilities
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly AccountFeatureRepository $features,
        private readonly AccountFeatureDecoratorBuilder $builder,
    ) {
    }

    public function handle(int $actorUserId, bool $canViewAll, string $accountPublicId): array
    {
        $account = $this->accounts->findByPublicId($accountPublicId);
        if (!$account) throw new \RuntimeException('الحساب غير موجود');

        if (!$canViewAll && (int) $account->userId !== $actorUserId) {
            throw new \RuntimeException('غير مسموح');
        }

        $base = new BaseAccountComponent(
            accountId: (int) $account->id,
            accountPublicId: (string) $accountPublicId,
            balance: (string) $account->balance,
        );

        $active = $this->features->listActiveByAccountId((int) $account->id);
        $decorated = $this->builder->apply($base, $active);

        return [
            'account_public_id' => $accountPublicId,
            'balance' => $decorated->balance(),
            'available_to_withdraw' => $decorated->availableToWithdraw(),
            'example_transfer_fee_on_100' => $decorated->transferFee('100.00'),
            'monthly_fixed_fees' => $decorated->monthlyFixedFees(),
            'enabled_features' => $decorated->enabledFeatures(),
        ];
    }
}
