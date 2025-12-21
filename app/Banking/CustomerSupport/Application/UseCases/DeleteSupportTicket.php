<?php

namespace App\Banking\CustomerSupport\Application\UseCases;

use Illuminate\Support\Facades\DB;
use App\Banking\CustomerSupport\Domain\Contracts\SupportTicketRepository;

final class DeleteSupportTicket
{
    public function __construct(private readonly SupportTicketRepository $tickets)
    {
    }

    public function handle(string $ticketPublicId): array
    {
        return DB::transaction(function () use ($ticketPublicId) {

            $t = $this->tickets->lockByPublicIdForUpdate($ticketPublicId);
            if (!$t) throw new \RuntimeException('التذكرة غير موجودة');

            $this->tickets->softDeleteById($t->id);

            return [
                'message' => 'تم حذف التذكرة (Soft Delete)',
                'ticket_public_id' => $t->publicId,
            ];
        });
    }
}
