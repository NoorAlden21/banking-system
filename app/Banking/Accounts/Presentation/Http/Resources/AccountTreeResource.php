<?php

namespace App\Banking\Accounts\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountTreeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->publicId(),
            'type' => $this->type(),
            'state' => $this->state(),
            'balance' => $this->balance(),
            'total_balance' => $this->totalBalance(),
            'children' => array_map(
                fn ($child) => (new self($child))->toArray($request),
                $this->children()
            ),
        ];
    }
}
