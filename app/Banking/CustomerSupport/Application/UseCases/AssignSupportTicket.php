<?php

namespace App\Banking\CustomerSupport\Application\UseCases;

use Illuminate\Support\Facades\DB;

use App\Banking\CustomerSupport\Application\DTOs\AssignSupportTicketData;
use App\Banking\CustomerSupport\Domain\Contracts\SupportTicketRepository;
use App\Banking\CustomerSupport\Domain\Events\SupportTicketAssigned;

final class AssignSupportTicket
{
    public function __construct(private readonly SupportTicketRepository $tickets)
    {
    }

    public function handle(int $actorUserId, string $ticketPublicId, AssignSupportTicketData $data): array
    {
        return DB::transaction(function () use ($actorUserId, $ticketPublicId, $data) {

            $t = $this->tickets->lockByPublicIdForUpdate($ticketPublicId);
            if (!$t) throw new \RuntimeException('التذكرة غير موجودة');

            $this->tickets->updateById($t->id, [
                'assigned_to_user_id' => $data->assignedToUserId,
            ]);

            DB::afterCommit(function () use ($t, $actorUserId, $data) {
                event(new SupportTicketAssigned(
                    ticketPublicId: $t->publicId,
                    ownerUserId: $t->ownerUserId,
                    assignedToUserId: $data->assignedToUserId,
                    assignedByUserId: $actorUserId,
                ));
            });

            return [
                'message' => 'تم إسناد التذكرة',
                'ticket_public_id' => $t->publicId,
                'assigned_to_user_id' => $data->assignedToUserId,
            ];
        });
    }
}
