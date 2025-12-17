<?php

namespace App\Banking\Accounts\Application\UseCases;

use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Domain\Services\AccountTreeBuilder;
use App\Banking\Accounts\Domain\Patterns\Composite\AccountGroup;

final class GetMyAccountTree
{
    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly AccountTreeBuilder $builder
    ) {
    }

    public function handle(int $authUserId): AccountGroup
    {
        $list = $this->accounts->listByUser($authUserId);
        return $this->builder->buildForUser($list);
    }
}
