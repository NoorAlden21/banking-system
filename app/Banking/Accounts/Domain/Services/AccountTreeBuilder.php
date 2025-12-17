<?php

namespace App\Banking\Accounts\Domain\Services;

use App\Banking\Accounts\Domain\Entities\Account;
use App\Banking\Accounts\Domain\Enums\AccountTypeEnum;
use App\Banking\Accounts\Domain\Patterns\Composite\AccountGroup;
use App\Banking\Accounts\Domain\Patterns\Composite\AccountLeaf;

final class AccountTreeBuilder
{
    /**
     * يبني شجرة حسابات لمستخدم واحد:
     * - يحدد الـGroup
     * - يضيف كل children تحته
     *
     * @param Account[] $accounts
     */
    public function buildForUser(array $accounts): AccountGroup
    {
        $groupEntity = $this->findGroupEntity($accounts);

        $group = new AccountGroup($groupEntity);

        foreach ($accounts as $acc) {
            if ($acc->type === AccountTypeEnum::GROUP) {
                continue;
            }

            if ($acc->parentId === $groupEntity->id) {
                $group->add(new AccountLeaf($acc));
            }
        }

        return $group;
    }

    /**
     * @param Account[] $accounts
     */
    private function findGroupEntity(array $accounts): Account
    {
        foreach ($accounts as $acc) {
            if ($acc->type === AccountTypeEnum::GROUP && $acc->parentId === null) {
                return $acc;
            }
        }

        throw new \RuntimeException('لا يوجد حساب Group للمستخدم. يجب إنشاؤه تلقائيًا عند أول عملية.');
    }
}
