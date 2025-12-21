<?php

namespace App\Banking\CustomerSupport\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class SupportTicketResource extends JsonResource
{
    public function toArray($request): array
    {
        $owner = $this->owner;
        $assigned = $this->assignedTo;

        return [
            'public_id' => (string) $this->public_id,
            'subject' => (string) $this->subject,
            'status' => (string) $this->status,
            'category' => $this->category ? (string) $this->category : null,
            'priority' => (string) $this->priority,

            'owner' => $owner ? [
                'id' => (int) $owner->id,
                'public_id' => (string) $owner->public_id,
                'name' => (string) $owner->name,
                'email' => (string) $owner->email,
            ] : null,

            'assigned_to' => $assigned ? [
                'id' => (int) $assigned->id,
                'public_id' => (string) $assigned->public_id,
                'name' => (string) $assigned->name,
                'email' => (string) $assigned->email,
            ] : null,

            'last_message_at' => optional($this->last_message_at)->toISOString(),
            'resolved_at' => optional($this->resolved_at)->toISOString(),
            'closed_at' => optional($this->closed_at)->toISOString(),

            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),

            // include messages فقط لو loaded
            'messages' => $this->relationLoaded('messages')
                ? SupportMessageResource::collection($this->messages)
                : null,
        ];
    }
}
