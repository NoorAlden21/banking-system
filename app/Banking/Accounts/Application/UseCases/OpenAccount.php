<?php

namespace App\Banking\Accounts\Application\UseCases;

use App\Banking\Accounts\Application\DTOs\OpenAccountData;
use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Domain\Entities\Account;
use App\Banking\Accounts\Domain\Enums\AccountTypeEnum;
use Illuminate\Support\Facades\DB;

final class OpenAccount
{
    public function __construct(private readonly AccountRepository $accounts)
    {
    }

    public function handle(int $userId, OpenAccountData $data): Account
    {
        if ($data->type === AccountTypeEnum::GROUP) {
            throw new \InvalidArgumentException('لا يمكن إنشاء حساب group عبر هذا المسار.');
        }

        return DB::transaction(function () use ($userId, $data) {
            $group = $this->accounts->findUserGroup($userId);
            if (!$group) {
                $group = $this->accounts->createGroup($userId);
            }

            return $this->accounts->createChildAccount(
                userId: $userId,
                groupId: $group->id,
                type: $data->type,
                dailyLimit: $data->dailyLimit,
                monthlyLimit: $data->monthlyLimit
            );
        });
    }
}
