<?php

namespace App\Banking\CustomerSupport\Domain\Contracts;

interface SupportMessageRepository
{
    public function create(array $data): void;
}
