<?php

namespace App\Banking\CustomerSupport\Application\UseCases;

use Illuminate\Support\Facades\DB;

use App\Banking\CustomerSupport\Application\DTOs\AddSupportMessageData;
use App\Banking\CustomerSupport\Domain\Contracts\SupportTicketRepository;
use App\Banking\CustomerSupport\Domain\Contracts\SupportMessageRepository;
use App\Banking\CustomerSupport\Domain\Events\SupportMessageAdded;

final class AddSupportTicketMessage
{
    public function __construct(
        private readonly SupportTicketRepository $tickets,
        private readonly SupportMessageRepository $messages,
    ) {
    }

    public function handle(
        int $actorUserId,
        bool $canViewAll,
        bool $canWriteInternal,
        string $ticketPublicId,
        AddSupportMessageData $data
    ): array {
        return DB::transaction(function () use ($actorUserId, $canViewAll, $canWriteInternal, $ticketPublicId, $data) {

            $t = $this->tickets->lockByPublicIdForUpdate($ticketPublicId);
            if (!$t) throw new \RuntimeException('التذكرة غير موجودة');

            if (!$canViewAll && $t->ownerUserId !== $actorUserId) {
                throw new \RuntimeException('غير مسموح');
            }

            if ($t->status === 'closed') {
                throw new \RuntimeException('لا يمكن الرد على تذكرة مغلقة');
            }

            $isInternal = $data->isInternal && $canWriteInternal;
            $this->messages->create([
                'ticket_id' => $t->id,
                'sender_user_id' => $actorUserId,
                'body' => $data->body,
                'is_internal' => $isInternal,
            ]);

            $nextStatus = $t->status;
            if (!$isInternal) {
                $isOwner = ($t->ownerUserId === $actorUserId);
                $nextStatus = $isOwner ? 'pending_staff' : 'pending_customer';
            }

            $this->tickets->updateById($t->id, [
                'status' => $nextStatus,
                'last_message_at' => now(),
                'resolved_at' => null,
            ]);

            DB::afterCommit(function () use ($t, $actorUserId, $isInternal) {
                event(new SupportMessageAdded(
                    ticketPublicId: $t->publicId,
                    ownerUserId: $t->ownerUserId,
                    assignedToUserId: $t->assignedToUserId,
                    senderUserId: $actorUserId,
                    isInternal: $isInternal,
                ));
            });

            return [
                'message' => 'تم إرسال الرسالة',
                'ticket_public_id' => $t->publicId,
                'status' => $nextStatus,
            ];
        });
    }
}
