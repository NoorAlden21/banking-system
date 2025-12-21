<?php

namespace App\Banking\Accounts\Domain\Entities;

final class AccountFeature
{
    public function __construct(
        public readonly string $featureKey,
        public readonly string $status,
        public readonly array $meta,
    ) {
    }
}
