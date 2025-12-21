<?php

namespace App\Banking\CustomerSupport\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Banking\CustomerSupport\Application\DTOs\CreateSupportTicketData;
use App\Banking\CustomerSupport\Domain\Contracts\SupportTicketRepository;
use App\Banking\CustomerSupport\Domain\Contracts\SupportMessageRepository;
use App\Banking\CustomerSupport\Domain\Events\SupportTicketCreated;

final class CreateSupportTicket
{
    public function __construct(
        private readonly SupportTicketRepository $tickets,
        private readonly SupportMessageRepository $messages,
    ) {
    }

    public function handle(int $actorUserId, CreateSupportTicketData $data): array
    {
        return DB::transaction(function () use ($actorUserId, $data) {

            $ticket = $this->tickets->create([
                'public_id' => (string) Str::uuid(),
                'owner_user_id' => $actorUserId,
                'created_by_user_id' => $actorUserId,
                'assigned_to_user_id' => null,
                'subject' => $data->subject,
                'category' => $data->category,
                'priority' => $data->priority,
                'status' => 'open',
                'last_message_at' => now(),
            ]);

            $this->messages->create([
                'ticket_id' => $ticket->id,
                'sender_user_id' => $actorUserId,
                'body' => $data->messageBody,
                'is_internal' => false,
            ]);

            DB::afterCommit(function () use ($ticket, $actorUserId, $data) {
                event(new SupportTicketCreated(
                    ticketPublicId: $ticket->publicId,
                    ownerUserId: $actorUserId,
                    assignedToUserId: null,
                    subject: $data->subject,
                ));
            });

            return [
                'message' => 'تم إنشاء التذكرة بنجاح',
                'ticket_public_id' => $ticket->publicId,
                'status' => $ticket->status,
            ];
        });
    }
}
