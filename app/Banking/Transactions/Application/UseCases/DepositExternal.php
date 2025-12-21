<?php

namespace App\Banking\Transactions\Application\UseCases;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

use App\Banking\Payments\Application\Services\PaymentGatewayResolver;
use App\Banking\Payments\Domain\ValueObjects\PaymentCharge;

use App\Banking\Transactions\Application\DTOs\DepositData;
use App\Banking\Transactions\Application\DTOs\DepositExternalData;
use App\Banking\Transactions\Application\DTOs\TransactionOutcome;

use App\Banking\Transactions\Application\Facades\BankingFacade;
use App\Banking\Transactions\Domain\Exceptions\TransactionRuleViolation;

final class DepositExternal
{
    public function __construct(
        private readonly PaymentGatewayResolver $resolver,
        private readonly BankingFacade $banking,
    ) {
    }

    public function handle(int $initiatorUserId, bool $canOperateAny, DepositExternalData $data): array
    {
        $currency = (string) Config::get('banking.currency', 'USD');

        $gateway = $this->resolver->resolve($data->gateway);

        $charge = new PaymentCharge(
            gateway: (string) ($data->gateway ?: 'card'),
            amount: $data->amount,
            currency: $currency,
            token: $data->paymentToken,
            meta: [
                'account_public_id' => $data->accountPublicId,
                'initiator_user_id' => $initiatorUserId,
            ],
        );

        $paymentRes = $gateway->charge($charge);

        if (!$paymentRes->success) {
            throw new TransactionRuleViolation('فشل الدفع عبر بوابة الدفع: ' . ($paymentRes->errorMessage ?? 'unknown'));
        }

        // 2) Post internal deposit transaction (ledger/balances) inside DB transaction
        // add payment reference into description (no schema change)
        $desc = trim((string) $data->description);
        $desc = $desc !== '' ? $desc : null;

        $ref = (string) ($paymentRes->reference ?? '');
        $suffix = $ref !== '' ? " | gateway={$charge->gateway} ref={$ref}" : " | gateway={$charge->gateway}";

        $depositDto = new DepositData(
            accountPublicId: $data->accountPublicId,
            amount: $data->amount,
            description: ($desc ? ($desc . $suffix) : ('External deposit' . $suffix)),
        );

        try {
            /** @var TransactionOutcome $outcome */
            $outcome = DB::transaction(function () use ($initiatorUserId, $depositDto) {
                return $this->banking->deposit($initiatorUserId, $depositDto);
            });
        } catch (\Throwable $e) {
            // 3) Best-effort refund (compensation) outside DB
            try {
                if ($ref !== '') {
                    $gateway->refund($ref, $data->amount, $currency);
                }
            } catch (\Throwable $ignored) {
                // ignore
            }

            throw $e;
        }

        return [
            'payment' => [
                'gateway' => $charge->gateway,
                'status' => $paymentRes->status,
                'reference' => $paymentRes->reference,
            ],
            'transaction' => [
                'message' => $outcome->message,
                'transaction_public_id' => $outcome->transactionPublicId,
                'status' => $outcome->status,
                'data' => $outcome->data,
            ],
        ];
    }
}
