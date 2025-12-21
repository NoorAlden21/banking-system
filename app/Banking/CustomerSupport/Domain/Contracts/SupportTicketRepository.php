<?php

namespace App\Banking\CustomerSupport\Domain\Contracts;

use App\Banking\CustomerSupport\Domain\Entities\SupportTicketRecord;
use App\Banking\CustomerSupport\Domain\Entities\SupportTicketForUpdate;

interface SupportTicketRepository
{
    public function create(array $data): SupportTicketRecord;

    public function lockByPublicIdForUpdate(string $publicId): ?SupportTicketForUpdate;

    public function updateById(int $id, array $data): void;

    public function softDeleteById(int $id): void;
}
