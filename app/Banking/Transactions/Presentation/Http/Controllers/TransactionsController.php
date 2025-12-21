<?php

namespace App\Banking\Transactions\Presentation\Http\Controllers;

use App\Banking\Shared\Application\DTOs\AuditEntryData;
use App\Banking\Shared\Domain\Contracts\AuditLogger;
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
use Illuminate\Support\Facades\Log;

final class TransactionsController
{
    public function __construct(
        private readonly BankingFacade $banking,
        private readonly IdempotencyStore $idem,
        private readonly AuditLogger $audit,
    ) {
    }

    private function actorRole($user): string
    {
        if (!$user) return 'guest';
        if (method_exists($user, 'getRoleNames')) {
            return (string) ($user->getRoleNames()->first() ?? 'user');
        }
        return 'user';
    }

    private function safeAudit($actor, string $action, ?string $subjectType = null, ?string $subjectPublicId = null, array $meta = []): void
    {
        try {
            $req = request();
            $this->audit->log(new AuditEntryData(
                actorUserId: (int) ($actor?->id ?? 0),
                actorRole: $this->actorRole($actor),
                action: $action,
                subjectType: $subjectType,
                subjectPublicId: $subjectPublicId,
                ip: $req?->ip(),
                userAgent: (string) ($req?->userAgent() ?? ''),
                meta: $meta,
            ));
        } catch (\Throwable $ignored) {
            Log::warning('AUDIT_FAILED', [
                'action' => $action,
                'error' => $ignored->getMessage(),
            ]);
        }
    }

    private function errorMeta(\Throwable $e): array
    {
        return ['error' => class_basename($e), 'message' => $e->getMessage()];
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
        $user = $request->user();
        $userId = (int) $user->id;

        $decision = $request->decision();
        $note = $request->note();

        // attempt
        $this->safeAudit($user, 'transactions.approval.decision.attempt', 'transaction', $publicId, [
            'decision' => $decision,
            'note' => $note,
        ]);

        try {
            $res = $useCase->handle(
                txPublicId: $publicId,
                managerUserId: $userId,
                decision: $decision,
                note: $note
            );

            $finalStatus = (string) ($res['status'] ?? '');
            $txPublicId  = (string) ($res['transaction_public_id'] ?? $publicId);

            DB::afterCommit(function () use ($user, $decision, $note, $finalStatus, $txPublicId) {
                $this->safeAudit($user, 'transactions.approval.decision.success', 'transaction', $txPublicId, [
                    'decision' => $decision,
                    'note' => $note,
                    'status' => $finalStatus,
                ]);

                if ($finalStatus === 'posted') {
                    event(new TransactionPosted($txPublicId));
                }
            });

            return response()->json($res, 200);
        } catch (\Throwable $e) {
            $this->safeAudit($user, 'transactions.approval.decision.failed', 'transaction', $publicId, array_merge([
                'decision' => $decision,
                'note' => $note,
            ], $this->errorMeta($e)));

            throw $e;
        }
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
        $user = $request->user();
        $userId = (int) $user->id;
        $idemKey = $this->idemKeyOrFail();

        $dto = new DepositData(
            accountPublicId: $request->string('account_public_id')->toString(),
            amount: (string) $request->input('amount'),
            description: $request->filled('description') ? $request->string('description')->toString() : null,
        );

        $this->safeAudit($user, 'transactions.deposit.attempt', 'account', $dto->accountPublicId, [
            'amount' => $dto->amount,
            'idempotency_key' => $idemKey,
        ]);

        $action = 'deposit';
        $hash = hash('sha256', json_encode([$dto->accountPublicId, $dto->amount, $dto->description], JSON_UNESCAPED_UNICODE));

        try {
            $idemRow = $this->idem->start($userId, $action, $idemKey, $hash);

            if ($idemRow->response_code && $idemRow->response_body) {
                $this->safeAudit($user, 'transactions.deposit.replayed', 'idempotency', null, [
                    'idempotency_key' => $idemKey,
                    'account_public_id' => $dto->accountPublicId,
                ]);

                return response()->json(json_decode($idemRow->response_body, true), (int) $idemRow->response_code);
            }

            return DB::transaction(function () use ($user, $userId, $dto, $idemRow, $idemKey) {
                $outcome = $this->banking->deposit($userId, $dto);

                $payload = $this->payloadFor($outcome);
                $code = $this->httpCodeFor($outcome);

                $this->idem->storeResponse(
                    $idemRow,
                    $code,
                    json_encode($payload, JSON_UNESCAPED_UNICODE),
                    $outcome->transactionPublicId
                );

                $txPublicId = (string) ($payload['transaction_public_id'] ?? '');

                DB::afterCommit(function () use ($user, $dto, $txPublicId, $payload, $idemKey) {
                    $this->safeAudit($user, 'transactions.deposit.success', 'transaction', $txPublicId, [
                        'status' => (string) ($payload['status'] ?? ''),
                        'account_public_id' => $dto->accountPublicId,
                        'amount' => $dto->amount,
                        'idempotency_key' => $idemKey,
                    ]);
                });

                return response()->json($payload, $code);
            });
        } catch (\Throwable $e) {
            $this->safeAudit($user, 'transactions.deposit.failed', 'account', $dto->accountPublicId, array_merge([
                'amount' => $dto->amount,
                'idempotency_key' => $idemKey,
            ], $this->errorMeta($e)));

            throw $e;
        }
    }

    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        $user = $request->user();
        $userId = (int) $user->id;

        $idemKey = $this->idemKeyOrFail();
        $canOperateAny = $user->can('transactions.operate-any');

        $dto = new WithdrawData(
            accountPublicId: $request->string('account_public_id')->toString(),
            amount: (string) $request->input('amount'),
            description: $request->filled('description') ? $request->string('description')->toString() : null,
        );

        // attempt
        $this->safeAudit($user, 'transactions.withdraw.attempt', 'account', $dto->accountPublicId, [
            'amount' => $dto->amount,
            'idempotency_key' => $idemKey,
            'can_operate_any' => $canOperateAny,
        ]);

        $action = 'withdraw';
        $hash = hash('sha256', json_encode([$dto->accountPublicId, $dto->amount, $dto->description], JSON_UNESCAPED_UNICODE));

        try {
            $idemRow = $this->idem->start($userId, $action, $idemKey, $hash);

            // replay
            if ($idemRow->response_code && $idemRow->response_body) {
                $this->safeAudit($user, 'transactions.withdraw.replayed', 'idempotency', null, [
                    'idempotency_key' => $idemKey,
                    'account_public_id' => $dto->accountPublicId,
                ]);

                return response()->json(json_decode($idemRow->response_body, true), (int) $idemRow->response_code);
            }

            return DB::transaction(function () use ($user, $userId, $dto, $idemRow, $idemKey, $canOperateAny) {
                $outcome = $this->banking->withdraw($userId, $dto, $canOperateAny);

                $payload = $this->payloadFor($outcome);
                $code = $this->httpCodeFor($outcome);

                $this->idem->storeResponse(
                    $idemRow,
                    $code,
                    json_encode($payload, JSON_UNESCAPED_UNICODE),
                    $outcome->transactionPublicId
                );

                $txPublicId = (string) ($payload['transaction_public_id'] ?? '');

                DB::afterCommit(function () use ($user, $dto, $txPublicId, $payload, $idemKey, $canOperateAny) {
                    $this->safeAudit($user, 'transactions.withdraw.success', 'transaction', $txPublicId, [
                        'status' => (string) ($payload['status'] ?? ''),
                        'account_public_id' => $dto->accountPublicId,
                        'amount' => $dto->amount,
                        'idempotency_key' => $idemKey,
                        'can_operate_any' => $canOperateAny,
                    ]);
                });

                return response()->json($payload, $code);
            });
        } catch (\Throwable $e) {
            $this->safeAudit($user, 'transactions.withdraw.failed', 'account', $dto->accountPublicId, array_merge([
                'amount' => $dto->amount,
                'idempotency_key' => $idemKey,
                'can_operate_any' => $canOperateAny,
            ], $this->errorMeta($e)));

            throw $e;
        }
    }

    public function transfer(TransferRequest $request): JsonResponse
    {
        $user = $request->user();
        $userId = (int) $user->id;

        $idemKey = $this->idemKeyOrFail();
        $canOperateAny = $user->can('transactions.operate-any');

        $dto = new TransferData(
            sourceAccountPublicId: $request->string('source_account_public_id')->toString(),
            destinationAccountPublicId: $request->string('destination_account_public_id')->toString(),
            amount: (string) $request->input('amount'),
            description: $request->filled('description') ? $request->string('description')->toString() : null,
        );

        // attempt
        $this->safeAudit($user, 'transactions.transfer.attempt', 'account', $dto->sourceAccountPublicId, [
            'source_account_public_id' => $dto->sourceAccountPublicId,
            'destination_account_public_id' => $dto->destinationAccountPublicId,
            'amount' => $dto->amount,
            'idempotency_key' => $idemKey,
            'can_operate_any' => $canOperateAny,
        ]);

        $action = 'transfer';
        $hash = hash('sha256', json_encode([
            $dto->sourceAccountPublicId,
            $dto->destinationAccountPublicId,
            $dto->amount,
            $dto->description,
        ], JSON_UNESCAPED_UNICODE));

        try {
            $idemRow = $this->idem->start($userId, $action, $idemKey, $hash);

            // replay
            if ($idemRow->response_code && $idemRow->response_body) {
                $this->safeAudit($user, 'transactions.transfer.replayed', 'idempotency', null, [
                    'idempotency_key' => $idemKey,
                    'source_account_public_id' => $dto->sourceAccountPublicId,
                    'destination_account_public_id' => $dto->destinationAccountPublicId,
                ]);

                return response()->json(json_decode($idemRow->response_body, true), (int) $idemRow->response_code);
            }

            return DB::transaction(function () use ($user, $userId, $dto, $idemRow, $idemKey, $canOperateAny) {
                $outcome = $this->banking->transfer($userId, $dto, $canOperateAny);

                $payload = $this->payloadFor($outcome);
                $code = $this->httpCodeFor($outcome);

                $this->idem->storeResponse(
                    $idemRow,
                    $code,
                    json_encode($payload, JSON_UNESCAPED_UNICODE),
                    $outcome->transactionPublicId
                );

                $txPublicId = (string) ($payload['transaction_public_id'] ?? '');

                DB::afterCommit(function () use ($user, $dto, $txPublicId, $payload, $idemKey, $canOperateAny) {
                    $this->safeAudit($user, 'transactions.transfer.success', 'transaction', $txPublicId, [
                        'status' => (string) ($payload['status'] ?? ''),
                        'source_account_public_id' => $dto->sourceAccountPublicId,
                        'destination_account_public_id' => $dto->destinationAccountPublicId,
                        'amount' => $dto->amount,
                        'idempotency_key' => $idemKey,
                        'can_operate_any' => $canOperateAny,
                    ]);
                });

                return response()->json($payload, $code);
            });
        } catch (\Throwable $e) {
            $this->safeAudit($user, 'transactions.transfer.failed', 'account', $dto->sourceAccountPublicId, array_merge([
                'source_account_public_id' => $dto->sourceAccountPublicId,
                'destination_account_public_id' => $dto->destinationAccountPublicId,
                'amount' => $dto->amount,
                'idempotency_key' => $idemKey,
                'can_operate_any' => $canOperateAny,
            ], $this->errorMeta($e)));

            throw $e;
        }
    }
}
