<?php

namespace App\Banking\Accounts\Application\DTOs;

final class OnboardCustomerData
{
    /**
     * @param OpenAccountData[] $accounts
     */
    public function __construct(
        public readonly CustomerData $customer,
        public readonly array $accounts,
    ) {
    }
}
