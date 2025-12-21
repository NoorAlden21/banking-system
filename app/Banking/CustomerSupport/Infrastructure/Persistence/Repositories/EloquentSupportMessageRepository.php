<?php

namespace App\Banking\CustomerSupport\Infrastructure\Persistence\Repositories;

use App\Banking\CustomerSupport\Domain\Contracts\SupportMessageRepository;
use App\Banking\CustomerSupport\Infrastructure\Persistence\Models\SupportMessageModel;

final class EloquentSupportMessageRepository implements SupportMessageRepository
{
    public function create(array $data): void
    {
        SupportMessageModel::query()->create($data);
    }
}
