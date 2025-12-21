<?php

namespace App\Banking\CustomerSupport\Infrastructure\Persistence\Repositories;

use App\Banking\CustomerSupport\Domain\Contracts\SupportTicketRepository;
use App\Banking\CustomerSupport\Domain\Entities\SupportTicketRecord;
use App\Banking\CustomerSupport\Domain\Entities\SupportTicketForUpdate;
use App\Banking\CustomerSupport\Infrastructure\Persistence\Models\SupportTicketModel;

final class EloquentSupportTicketRepository implements SupportTicketRepository
{
    public function create(array $data): SupportTicketRecord
    {
        $m = SupportTicketModel::query()->create($data);

        return new SupportTicketRecord(
            id: (int) $m->id,
            publicId: (string) $m->public_id,
            status: (string) $m->status,
        );
    }

    public function lockByPublicIdForUpdate(string $publicId): ?SupportTicketForUpdate
    {
        $m = SupportTicketModel::query()
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->first();

        if (!$m) return null;

        return new SupportTicketForUpdate(
            id: (int) $m->id,
            publicId: (string) $m->public_id,
            ownerUserId: (int) $m->owner_user_id,
            assignedToUserId: $m->assigned_to_user_id ? (int) $m->assigned_to_user_id : null,
            status: (string) $m->status,
            subject: (string) $m->subject,
        );
    }

    public function updateById(int $id, array $data): void
    {
        SupportTicketModel::query()->where('id', $id)->update($data);
    }

    public function softDeleteById(int $id): void
    {
        $m = SupportTicketModel::query()->find($id);
        if ($m) $m->delete();
    }
}
