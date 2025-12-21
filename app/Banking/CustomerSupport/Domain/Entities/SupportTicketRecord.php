<?php

namespace App\Banking\CustomerSupport\Domain\Entities;

final class SupportTicketRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $publicId,
        public readonly string $status,
    ) {
    }
}
