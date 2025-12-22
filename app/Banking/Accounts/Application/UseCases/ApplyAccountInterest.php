<?php

namespace App\Banking\Accounts\Application\UseCases;

use Illuminate\Support\Facades\DB;

use App\Banking\Accounts\Application\DTOs\ApplyInterestData;
use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Domain\Services\Interest\InterestCalculator;

use App\Banking\Transactions\Application\Facades\BankingFacade;
use App\Banking\Transactions\Application\DTOs\DepositData;

final class ApplyAccountInterest
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly InterestCalculator $calculator,
        private readonly BankingFacade $banking,
    ) {
    }

    public function handle(int $actorUserId, bool $canViewAll, string $accountPublicId, ApplyInterestData $data): array
    {
        return DB::transaction(function () use ($actorUserId, $canViewAll, $accountPublicId, $data) {

            $account = $this->accounts->findByPublicId($accountPublicId);
            if (!$account) throw new \RuntimeException('الحساب غير موجود');

            if (method_exists($account, 'isGroup') && $account->isGroup()) {
                throw new \RuntimeException('لا يمكن تطبيق فائدة على حساب group');
            }

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

            $interest = (string) ($preview['interest'] ?? '0.00');
            if (((float)$interest) <= 0.0) {
                return [
                    'message' => 'لا توجد فائدة مستحقة للتطبيق',
                    'account_public_id' => $accountPublicId,
                    'interest' => $interest,
                    'preview' => $preview,
                ];
            }

            $outcome = $this->banking->deposit($actorUserId, new DepositData(
                accountPublicId: $accountPublicId,
                amount: $interest,
                description: 'Interest accrual',
            ));

            return [
                'message' => 'تم تطبيق الفائدة بنجاح',
                'account_public_id' => $accountPublicId,
                'account_type' => $type,
                'interest' => $interest,
                'market' => (string) ($preview['market'] ?? ''),
                'days' => (int) ($preview['days'] ?? $data->days),
                'transaction_public_id' => $outcome->transactionPublicId,
                'transaction_status' => $outcome->status,
            ];
        });
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
