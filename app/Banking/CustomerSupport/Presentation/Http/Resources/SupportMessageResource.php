<?php

namespace App\Banking\CustomerSupport\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class SupportMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        $sender = $this->sender;

        return [
            'id' => (int) $this->id,
            'body' => (string) $this->body,
            'is_internal' => (bool) $this->is_internal,
            'sender' => $sender ? [
                'id' => (int) $sender->id,
                'public_id' => (string) $sender->public_id,
                'name' => (string) $sender->name,
                'email' => (string) $sender->email,
            ] : null,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
