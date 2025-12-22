<?php

namespace App\Banking\Accounts\Application\UseCases;

use App\Banking\Accounts\Application\DTOs\PreviewInterestData;
use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Domain\Services\Interest\InterestCalculator;

final class PreviewAccountInterest
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly InterestCalculator $calculator,
    ) {
    }

    public function handle(int $actorUserId, bool $canViewAll, string $accountPublicId, PreviewInterestData $data): array
    {
        $account = $this->accounts->findByPublicId($accountPublicId);
        if (!$account) throw new \RuntimeException('الحساب غير موجود');

        if (method_exists($account, 'isGroup') && $account->isGroup()) {
            throw new \RuntimeException('لا يمكن حساب فائدة لحساب group');
        }

        // ownership check unless staff
        $ownerId = (int) ($account->userId ?? 0);
        if (!$canViewAll && $ownerId !== $actorUserId) {
            throw new \RuntimeException('غير مصرح لك');
        }

        $principal = $this->balanceString($account);
        $type = $this->typeString($account);

        $preview = $this->calculator->preview(
            principal: $principal,
            accountType: $type,
            days: $data->days,
            marketCode: $data->market,
        );

        return [
            'account_public_id' => (string) ($account->publicId ?? $accountPublicId),
            'account_type' => $type,
            'data' => $preview,
        ];
    }

    private function balanceString(object $account): string
    {
        $b = $account->balance ?? '0';
        if (is_object($b)) {
            if (property_exists($b, 'amount')) return (string) $b->amount;
            if (method_exists($b, 'amount')) return (string) $b->amount();
            if (method_exists($b, '__toString')) return (string) $b;
        }
        return (string) $b;
    }

    private function typeString(object $account): string
    {
        $t = $account->type ?? 'checking';
        if (is_object($t)) {
            if (property_exists($t, 'value')) return (string) $t->value;
            if (method_exists($t, 'value')) return (string) $t->value();
            if (method_exists($t, '__toString')) return (string) $t;
        }
        return (string) $t;
    }
}
