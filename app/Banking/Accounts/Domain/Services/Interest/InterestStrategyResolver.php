<?php

namespace App\Banking\Accounts\Domain\Services\Interest;

use App\Banking\Accounts\Domain\Patterns\Strategy\InterestStrategy;
use App\Banking\Accounts\Domain\Patterns\Strategy\Strategies\SavingsInterestStrategy;
use App\Banking\Accounts\Domain\Patterns\Strategy\Strategies\CheckingInterestStrategy;
use App\Banking\Accounts\Domain\Patterns\Strategy\Strategies\InvestmentInterestStrategy;
use App\Banking\Accounts\Domain\Patterns\Strategy\Strategies\LoanInterestStrategy;

final class InterestStrategyResolver
{
    public function __construct(
        private readonly SavingsInterestStrategy $savings,
        private readonly CheckingInterestStrategy $checking,
        private readonly InvestmentInterestStrategy $investment,
        private readonly LoanInterestStrategy $loan,
    ) {
    }

    public function resolve(string $accountType): InterestStrategy
    {
        return match ($accountType) {
            'savings'    => $this->savings,
            'checking'   => $this->checking,
            'investment' => $this->investment,
            'loan'       => $this->loan,
            default      => $this->checking, // safe fallback
        };
    }
}
