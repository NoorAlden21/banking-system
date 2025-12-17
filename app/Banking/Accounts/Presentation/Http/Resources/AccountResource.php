<?php

namespace App\Banking\Accounts\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->publicId,
            'user_id' => $this->userId,
            'parent_id' => $this->parentId,
            'type' => $this->type->value,
            'state' => $this->state->value,
            'balance' => $this->balance,
            'daily_limit' => $this->dailyLimit,
            'monthly_limit' => $this->monthlyLimit,
            'closed_at' => $this->closedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
