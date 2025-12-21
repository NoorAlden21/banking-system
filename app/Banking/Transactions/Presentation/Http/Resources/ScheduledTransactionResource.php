<?php

namespace App\Banking\Transactions\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class ScheduledTransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        $a = is_array($this->resource) ? $this->resource : $this->resource->toArray();

        return [
            'public_id' => $a['public_id'] ?? null,
            'owner_user_id' => $a['owner_user_id'] ?? null,
            'type' => $a['type'] ?? 'transfer',

            'amount' => $a['amount'] ?? null,
            'currency' => $a['currency'] ?? null,
            'description' => $a['description'] ?? null,

            'frequency' => $a['frequency'] ?? null,
            'interval' => $a['interval'] ?? null,
            'day_of_week' => $a['day_of_week'] ?? null,
            'day_of_month' => $a['day_of_month'] ?? null,
            'run_time' => $a['run_time'] ?? null,

            'status' => $a['status'] ?? null,
            'start_at' => $a['start_at'] ?? null,
            'end_at' => $a['end_at'] ?? null,

            'next_run_at' => $a['next_run_at'] ?? null,
            'last_run_at' => $a['last_run_at'] ?? null,
            'last_transaction_public_id' => $a['last_transaction_public_id'] ?? null,
            'last_status' => $a['last_status'] ?? null,
            'last_error' => $a['last_error'] ?? null,

            'runs_count' => $a['runs_count'] ?? 0,
            'created_at' => $a['created_at'] ?? null,
        ];
    }
}
