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

use App\Banking\Transactions\Application\UseCases\ListTransactions;
use App\Banking\Transactions\Application\UseCases\ShowTransaction;
use App\Banking\Transactions\Application\UseCases\DecideTransactionApproval;
use App\Banking\Transactions\Presentation\Http\Requests\ListTransactionsRequest;
use App\Banking\Transactions\Presentation\Http\Requests\DecideApprovalRequest;
use App\Banking\Transactions\Domain\Events\TransactionPosted;

use App\Banking\Transactions\Infrastructure\Persistence\Repositories\IdempotencyStore;

final class TransactionsController
{
    public function __construct(
        private readonly BankingFacade $banking,
        private readonly IdempotencyStore $idem,
    ) {
    }

    public function index(ListTransactionsRequest $request, ListTransactions $useCase): JsonResponse
    {
        $user = $request->user();
        $scope = $request->scope();

        $canViewAll = $user->can('transactions.view-all');

        $result = $useCase->handle(
            actorUserId: (int) $user->id,
            canViewAll: $canViewAll,
            scope: $scope,
            filters: $request->filters(),
            perPage: $request->perPage(),
            page: $request->page(),
        );

        return response()->json($result);
    }

    public function show(string $publicId, ListTransactionsRequest $request, ShowTransaction $useCase): JsonResponse
    {
        $user = $request->user();
        $scope = $request->scope();
        $canViewAll = $user->can('transactions.view-all');

        $detail = $useCase->handle(
            actorUserId: (int) $user->id,
            canViewAll: $canViewAll,
            publicId: $publicId,
            scope: $scope,
        );

        if (!$detail) {
            return response()->json(['message' => 'غير موجود'], 404);
        }

        return response()->json(['data' => $detail]);
    }

    public function pendingApprovals(ListTransactionsRequest $request, ListTransactions $useCase): JsonResponse
    {
        // alias: نفس index لكن forced filters
        $user = $request->user();

        $result = $useCase->handle(
            actorUserId: (int) $user->id,
            canViewAll: true,
            scope: 'all',
            filters: array_merge($request->filters(), ['status' => 'pending_approval']),
            perPage: $request->perPage(),
            page: $request->page(),
        );

        return response()->json($result);
    }


    public function decision(string $publicId, DecideApprovalRequest $request, DecideTransactionApproval $useCase): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $res = $useCase->handle(
            txPublicId: $publicId,
            managerUserId: $userId,
            decision: $request->decision(),
            note: $request->note()
        );

        // لو approve وstatus posted -> dispatch event after commit
        if (($res['status'] ?? null) === 'posted') {
            event(new TransactionPosted((string) $res['transaction_public_id']));
        }

        return response()->json($res, 200);
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
        $canOperateAny = $request->user()->can('transactions.operate-any');

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

        return DB::transaction(function () use ($userId, $dto, $idemRow, $canOperateAny) {
            $outcome = $this->banking->withdraw($userId, $dto, $canOperateAny);

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
        $canOperateAny = $request->user()->can('transactions.operate-any');

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

        return DB::transaction(function () use ($userId, $dto, $idemRow, $canOperateAny) {
            $outcome = $this->banking->transfer($userId, $dto, $canOperateAny);

            $payload = $this->payloadFor($outcome);
            $code = $this->httpCodeFor($outcome);

            $this->idem->storeResponse($idemRow, $code, json_encode($payload, JSON_UNESCAPED_UNICODE), $outcome->transactionPublicId);

            return response()->json($payload, $code);
        });
    }
}
