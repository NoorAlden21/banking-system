<?php

namespace App\Banking\Transactions\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

use App\Banking\Transactions\Application\Facades\BankingFacade;
use App\Banking\Transactions\Application\DTOs\DepositData;
use App\Banking\Transactions\Application\DTOs\WithdrawData;
use App\Banking\Transactions\Application\DTOs\TransferData;
use App\Banking\Transactions\Application\DTOs\TransactionOutcome;

use App\Banking\Transactions\Presentation\Http\Requests\DepositRequest;
use App\Banking\Transactions\Presentation\Http\Requests\WithdrawRequest;
use App\Banking\Transactions\Presentation\Http\Requests\TransferRequest;

use App\Banking\Transactions\Infrastructure\Persistence\Repositories\IdempotencyStore;

final class TransactionsController
{
    public function __construct(
        private readonly BankingFacade $banking,
        private readonly IdempotencyStore $idem,
    ) {
    }

    private function idemKeyOrFail(): string
    {
        $key = request()->header('Idempotency-Key');
        if (!$key) abort(422, 'Idempotency-Key header مطلوب لمنع تكرار العملية');
        return (string) $key;
    }

    private function httpCodeFor(TransactionOutcome $o): int
    {
        return $o->status === 'pending_approval' ? 202 : 201;
    }

    private function payloadFor(TransactionOutcome $o): array
    {
        return array_merge([
            'message' => $o->message,
            'transaction_public_id' => $o->transactionPublicId,
            'status' => $o->status,
        ], $o->data);
    }

    public function deposit(DepositRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $idemKey = $this->idemKeyOrFail();

        $dto = new DepositData(
            accountPublicId: $request->string('account_public_id')->toString(),
            amount: (string) $request->input('amount'),
            description: $request->filled('description') ? $request->string('description')->toString() : null,
        );

        $action = 'deposit';
        $hash = hash('sha256', json_encode([$dto->accountPublicId, $dto->amount, $dto->description], JSON_UNESCAPED_UNICODE));

        // Idempotency: replay أو start
        $idemRow = $this->idem->start($userId, $action, $idemKey, $hash);
        if ($idemRow->response_code && $idemRow->response_body) {
            return response()->json(json_decode($idemRow->response_body, true), (int) $idemRow->response_code);
        }

        return DB::transaction(function () use ($userId, $dto, $idemRow) {
            $outcome = $this->banking->deposit($userId, $dto);

            $payload = $this->payloadFor($outcome);
            $code = $this->httpCodeFor($outcome);

            $this->idem->storeResponse($idemRow, $code, json_encode($payload, JSON_UNESCAPED_UNICODE), $outcome->transactionPublicId);

            return response()->json($payload, $code);
        });
    }

    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $idemKey = $this->idemKeyOrFail();

        $dto = new WithdrawData(
            accountPublicId: $request->string('account_public_id')->toString(),
            amount: (string) $request->input('amount'),
            description: $request->filled('description') ? $request->string('description')->toString() : null,
        );

        $action = 'withdraw';
        $hash = hash('sha256', json_encode([$dto->accountPublicId, $dto->amount, $dto->description], JSON_UNESCAPED_UNICODE));

        $idemRow = $this->idem->start($userId, $action, $idemKey, $hash);
        if ($idemRow->response_code && $idemRow->response_body) {
            return response()->json(json_decode($idemRow->response_body, true), (int) $idemRow->response_code);
        }

        return DB::transaction(function () use ($userId, $dto, $idemRow) {
            $outcome = $this->banking->withdraw($userId, $dto);

            $payload = $this->payloadFor($outcome);
            $code = $this->httpCodeFor($outcome);

            $this->idem->storeResponse($idemRow, $code, json_encode($payload, JSON_UNESCAPED_UNICODE), $outcome->transactionPublicId);

            return response()->json($payload, $code);
        });
    }

    public function transfer(TransferRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $idemKey = $this->idemKeyOrFail();

        $dto = new TransferData(
            sourceAccountPublicId: $request->string('source_account_public_id')->toString(),
            destinationAccountPublicId: $request->string('destination_account_public_id')->toString(),
            amount: (string) $request->input('amount'),
            description: $request->filled('description') ? $request->string('description')->toString() : null,
        );

        $action = 'transfer';
        $hash = hash('sha256', json_encode([$dto->sourceAccountPublicId, $dto->destinationAccountPublicId, $dto->amount, $dto->description], JSON_UNESCAPED_UNICODE));

        $idemRow = $this->idem->start($userId, $action, $idemKey, $hash);
        if ($idemRow->response_code && $idemRow->response_body) {
            return response()->json(json_decode($idemRow->response_body, true), (int) $idemRow->response_code);
        }

        return DB::transaction(function () use ($userId, $dto, $idemRow) {
            $outcome = $this->banking->transfer($userId, $dto);

            $payload = $this->payloadFor($outcome);
            $code = $this->httpCodeFor($outcome);

            $this->idem->storeResponse($idemRow, $code, json_encode($payload, JSON_UNESCAPED_UNICODE), $outcome->transactionPublicId);

            return response()->json($payload, $code);
        });
    }
}
