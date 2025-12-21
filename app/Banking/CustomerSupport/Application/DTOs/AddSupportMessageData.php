<?php

namespace App\Banking\CustomerSupport\Application\DTOs;

final class AddSupportMessageData
{
    public function __construct(
        public readonly string $body,
        public readonly bool $isInternal,
    ) {
    }
}
