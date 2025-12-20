<?php

namespace App\Banking\Transactions\Domain\Services;

use Illuminate\Support\Facades\Config;

use App\Banking\Transactions\Application\DTOs\DepositData;
use App\Banking\Transactions\Application\DTOs\WithdrawData;
use App\Banking\Transactions\Application\DTOs\TransferData;
use App\Banking\Transactions\Application\DTOs\TransactionOutcome;

use App\Banking\Transactions\Domain\Entities\TransactionForPosting;

use App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility\TxContext;
use App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility\TxStep;

use App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility\ValidateBasics;
use App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility\CheckAccountState;
use App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility\LimitChecks;
use App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility\AMLRules;
use App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility\ApprovalDecision;
use App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility\PostToLedger;

final class TransactionProcessor
{
    public function __construct(
        private readonly ValidateBasics $validateBasics,
        private readonly CheckAccountState $checkAccountState,
        private readonly LimitChecks $limitChecks,
        private readonly AMLRules $amlRules,
        private readonly ApprovalDecision $approvalDecision,
        private readonly PostToLedger $postToLedger,
    ) {
    }

    private function run(array $steps, TxContext $ctx): array
    {
        $last = fn (TxContext $c) => $c->outcome ?? [
            'message' => 'انتهت السلسلة بدون نتيجة',
            'status' => 'error',
        ];

        $runner = array_reduce(
            array_reverse($steps),
            fn ($next, TxStep $step) => fn (TxContext $c) => $step->handle($c, $next),
            $last
        );

        return $runner($ctx);
    }

    private function outcomeFrom(array $payload): TransactionOutcome
    {
        return new TransactionOutcome(
            message: (string) ($payload['message'] ?? ''),
            transactionPublicId: (string) ($payload['transaction_public_id'] ?? ''),
            status: (string) ($payload['status'] ?? ''),
            data: array_diff_key($payload, array_flip(['message', 'transaction_public_id', 'status']))
        );
    }

    public function deposit(int $initiatorUserId, DepositData $data): TransactionOutcome
    {
        $currency = (string) Config::get('banking.currency', 'USD');

        $ctx = new TxContext(
            initiatorUserId: $initiatorUserId,
            canOperateAny: true,
            type: 'deposit',
            amount: $data->amount,
            currency: $currency,
            description: $data->description,
            sourceAccountPublicId: null,
            destinationAccountPublicId: $data->accountPublicId,
        );

        $payload = $this->run([
            $this->validateBasics,
            $this->checkAccountState,
            $this->amlRules,
            $this->postToLedger,
        ], $ctx);

        return $this->outcomeFrom($payload);
    }

    public function withdraw(int $initiatorUserId, WithdrawData $data, bool $canOperateAny): TransactionOutcome
    {
        $currency = (string) Config::get('banking.currency', 'USD');

        $ctx = new TxContext(
            initiatorUserId: $initiatorUserId,
            canOperateAny: $canOperateAny,
            type: 'withdraw',
            amount: $data->amount,
            currency: $currency,
            description: $data->description,
            sourceAccountPublicId: $data->accountPublicId,
            destinationAccountPublicId: null,
        );

        $payload = $this->run([
            $this->validateBasics,
            $this->checkAccountState,
            $this->limitChecks,
            $this->amlRules,
            $this->postToLedger,
        ], $ctx);

        return $this->outcomeFrom($payload);
    }

    public function transfer(int $initiatorUserId, TransferData $data, bool $canOperateAny): TransactionOutcome
    {
        $currency = (string) Config::get('banking.currency', 'USD');

        $ctx = new TxContext(
            initiatorUserId: $initiatorUserId,
            canOperateAny: $canOperateAny,
            type: 'transfer',
            amount: $data->amount,
            currency: $currency,
            description: $data->description,
            sourceAccountPublicId: $data->sourceAccountPublicId,
            destinationAccountPublicId: $data->destinationAccountPublicId,
        );

        $payload = $this->run([
            $this->validateBasics,
            $this->checkAccountState,
            $this->limitChecks,
            $this->amlRules,
            $this->approvalDecision,
            $this->postToLedger,
        ], $ctx);

        return $this->outcomeFrom($payload);
    }

    public function postApprovedTransfer(int $managerUserId, TransactionForPosting $tx): TransactionOutcome
    {
        // manager is staff
        $ctx = new TxContext(
            initiatorUserId: $managerUserId,
            canOperateAny: true,
            type: 'transfer',
            amount: $tx->amount,
            currency: $tx->currency,
            description: $tx->description,
            sourceAccountPublicId: $tx->sourceAccountPublicId,
            destinationAccountPublicId: $tx->destinationAccountPublicId,
            existingTransactionId: $tx->id,
            existingTransactionPublicId: $tx->publicId,
        );

        $payload = $this->run([
            $this->validateBasics,
            $this->checkAccountState,
            $this->limitChecks,
            $this->amlRules,
            $this->postToLedger, // approval posting
        ], $ctx);

        return $this->outcomeFrom($payload);
    }
}
