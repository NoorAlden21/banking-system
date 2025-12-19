<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Repositories;

use App\Banking\Transactions\Domain\Exceptions\IdempotencyConflict;
use App\Banking\Transactions\Infrastructure\Persistence\Models\IdempotencyKeyModel;

final class IdempotencyStore
{
    public function start(int $userId, string $action, string $key, string $requestHash): IdempotencyKeyModel
    {
        $row = IdempotencyKeyModel::query()
            ->where('initiator_user_id', $userId)
            ->where('action', $action)
            ->where('idempotency_key', $key)
            ->first();

        if ($row) {
            // نفس الطلب سابقًا
            if ($row->request_hash !== $requestHash) {
                throw new IdempotencyConflict('Idempotency-Key مستخدم مع payload مختلف');
            }

            // لو عنده response محفوظة رجّعها للـcontroller/usecase
            return $row;
        }

        return IdempotencyKeyModel::query()->create([
            'initiator_user_id' => $userId,
            'action' => $action,
            'idempotency_key' => $key,
            'request_hash' => $requestHash,
            'locked_at' => now(),
        ]);
    }

    public function storeResponse(IdempotencyKeyModel $row, int $code, string $body, ?string $transactionPublicId): void
    {
        $row->response_code = $code;
        $row->response_body = $body;
        $row->transaction_public_id = $transactionPublicId;
        $row->locked_at = null;
        $row->save();
    }
}
