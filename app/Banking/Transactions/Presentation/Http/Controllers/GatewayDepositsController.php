<?php

namespace App\Banking\Transactions\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

use App\Banking\Shared\Domain\Contracts\AuditLogger;
use App\Banking\Shared\Application\DTOs\AuditEntryData;

use App\Banking\Transactions\Application\DTOs\DepositExternalData;
use App\Banking\Transactions\Application\UseCases\DepositExternal;
use App\Banking\Transactions\Presentation\Http\Requests\DepositExternalRequest;

use App\Banking\Transactions\Infrastructure\Persistence\Repositories\IdempotencyStore;

final class GatewayDepositsController
{
    public function __construct(
        private readonly DepositExternal $useCase,
        private readonly IdempotencyStore $idem,
        private readonly AuditLogger $audit,
    ) {
    }

    private function idemKeyOrFail(): string
    {
        $key = request()->header('Idempotency-Key');
        if (!$key) abort(422, 'Idempotency-Key header مطلوب لمنع تكرار العملية');
        return (string) $key;
    }

    /** never store secrets (token) */
    private function safeMeta(array $meta): array
    {
        unset($meta['payment_token'], $meta['token'], $meta['access_token'], $meta['plain_password']);
        return $meta;
    }

    private function errorMeta(\Throwable $e): array
    {
        return [
            'error_class' => get_class($e),
            'error_message' => $e->getMessage(),
        ];
    }

    private function safeAudit(?object $user, string $action, ?string $subjectType, ?string $subjectPublicId, array $meta = []): void
    {
        try {
            $role = null;
            if ($user && method_exists($user, 'getRoleNames')) {
                $role = (string) ($user->getRoleNames()->first() ?: null);
            }

            $this->audit->log(new AuditEntryData(
                actorUserId: $user ? (int) $user->id : null,
                actorRole: $role,
                action: $action,
                subjectType: $subjectType,
                subjectPublicId: $subjectPublicId,
                ip: request()->ip(),
                userAgent: request()->userAgent(),
                meta: $this->safeMeta($meta),
            ));
        } catch (\Throwable $e) {
            logger()->warning('AUDIT_FAILED', ['action' => $action, 'error' => $e->getMessage()]);
        }
    }

    public function depositExternal(DepositExternalRequest $request): JsonResponse
    {
        $user = $request->user();
        $userId = (int) $user->id;

        $idemKey = $this->idemKeyOrFail();

        $canOperateAny = $user->can('transactions.operate-any'); // staff

        $dto = new DepositExternalData(
            accountPublicId: $request->string('account_public_id')->toString(),
            amount: (string) $request->input('amount'),
            description: $request->filled('description') ? $request->string('description')->toString() : null,
            gateway: $request->filled('gateway') ? $request->string('gateway')->toString() : null,
            paymentToken: $request->string('payment_token')->toString(),
        );

        $this->safeAudit($user, 'payments.deposit_external.attempt', 'account', $dto->accountPublicId, [
            'account_public_id' => $dto->accountPublicId,
            'amount' => $dto->amount,
            'gateway' => $dto->gateway ?: 'card',
            'idempotency_key' => $idemKey,
            'can_operate_any' => $canOperateAny,
        ]);

        $action = 'deposit_external';
        $hash = hash('sha256', json_encode([
            $dto->accountPublicId,
            $dto->amount,
            $dto->description,
            $dto->gateway,
            hash('sha256', $dto->paymentToken),
        ], JSON_UNESCAPED_UNICODE));

        try {
            $idemRow = $this->idem->start($userId, $action, $idemKey, $hash);

            if ($idemRow->response_code && $idemRow->response_body) {
                $this->safeAudit($user, 'payments.deposit_external.replayed', 'idempotency', null, [
                    'idempotency_key' => $idemKey,
                    'account_public_id' => $dto->accountPublicId,
                ]);

                return response()->json(json_decode($idemRow->response_body, true), (int) $idemRow->response_code);
            }

            $result = $this->useCase->handle($userId, $canOperateAny, $dto);

            return DB::transaction(function () use ($user, $idemRow, $result, $idemKey, $dto, $canOperateAny) {
                $payload = [
                    'message' => 'تم تنفيذ الإيداع عبر بوابة الدفع بنجاح',
                    'payment' => $result['payment'],
                    'transaction' => $result['transaction'],
                ];

                $code = 201;

                $txPublicId = (string) ($payload['transaction']['transaction_public_id'] ?? '');
                $this->idem->storeResponse($idemRow, $code, json_encode($payload, JSON_UNESCAPED_UNICODE), $txPublicId ?: null);

                DB::afterCommit(function () use ($user, $payload, $idemKey, $dto, $canOperateAny) {
                    $this->safeAudit($user, 'payments.deposit_external.success', 'transaction', (string)($payload['transaction']['transaction_public_id'] ?? null), [
                        'status' => (string)($payload['transaction']['status'] ?? ''),
                        'gateway' => (string)($payload['payment']['gateway'] ?? ''),
                        'payment_reference' => (string)($payload['payment']['reference'] ?? ''),
                        'account_public_id' => $dto->accountPublicId,
                        'amount' => $dto->amount,
                        'idempotency_key' => $idemKey,
                        'can_operate_any' => $canOperateAny,
                    ]);
                });

                return response()->json($payload, $code);
            });
        } catch (\Throwable $e) {
            $this->safeAudit($user, 'payments.deposit_external.failed', 'account', $dto->accountPublicId, array_merge([
                'account_public_id' => $dto->accountPublicId,
                'amount' => $dto->amount,
                'gateway' => $dto->gateway ?: 'card',
                'idempotency_key' => $idemKey,
                'can_operate_any' => $canOperateAny,
            ], $this->errorMeta($e)));

            throw $e;
        }
    }
}
