<?php

namespace App\Banking\CustomerSupport\Application\UseCases;

use Illuminate\Support\Facades\DB;

use App\Banking\CustomerSupport\Application\DTOs\ChangeSupportTicketStatusData;
use App\Banking\CustomerSupport\Domain\Contracts\SupportTicketRepository;
use App\Banking\CustomerSupport\Domain\Events\SupportTicketStatusChanged;

final class ChangeSupportTicketStatus
{
    public function __construct(private readonly SupportTicketRepository $tickets)
    {
    }

    public function handle(
        int $actorUserId,
        bool $canViewAll,
        string $ticketPublicId,
        ChangeSupportTicketStatusData $data
    ): array {
        $allowed = ['open', 'pending_staff', 'pending_customer', 'resolved', 'closed'];
        if (!in_array($data->status, $allowed, true)) {
            throw new \RuntimeException('حالة غير صحيحة');
        }

        return DB::transaction(function () use ($actorUserId, $canViewAll, $ticketPublicId, $data) {

            $t = $this->tickets->lockByPublicIdForUpdate($ticketPublicId);
            if (!$t) throw new \RuntimeException('التذكرة غير موجودة');

            if (!$canViewAll) {
                if ($t->ownerUserId !== $actorUserId) throw new \RuntimeException('غير مسموح');
                if ($data->status !== 'closed') throw new \RuntimeException('العميل يمكنه فقط إغلاق التذكرة');
            }

            $update = ['status' => $data->status];

            if ($data->status === 'resolved') {
                $update['resolved_at'] = now();
            }
            if ($data->status === 'closed') {
                $update['closed_at'] = now();
            }
            if (in_array($data->status, ['open', 'pending_staff', 'pending_customer'], true)) {
                $update['resolved_at'] = null;
                $update['closed_at'] = null;
            }

            $this->tickets->updateById($t->id, $update);

            DB::afterCommit(function () use ($t, $actorUserId, $data) {
                event(new SupportTicketStatusChanged(
                    ticketPublicId: $t->publicId,
                    ownerUserId: $t->ownerUserId,
                    assignedToUserId: $t->assignedToUserId,
                    newStatus: $data->status,
                    changedByUserId: $actorUserId,
                ));
            });

            return [
                'message' => 'تم تحديث حالة التذكرة',
                'ticket_public_id' => $t->publicId,
                'status' => $data->status,
            ];
        });
    }
}
