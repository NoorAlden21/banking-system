<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Banking\Accounts\Infrastructure\Persistence\Models\AccountModel;

class CustomerAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::query()
            ->whereIn('email', ['c1@gmail.com', 'c2@gmail.com', 'c3@gmail.com'])
            ->get()
            ->keyBy('email');

        $plans = [
            'c1@gmail.com' => [
                ['type' => 'savings',  'balance' => '5000.00',  'daily_limit' => '500.00',  'monthly_limit' => '5000.00'],
                ['type' => 'checking', 'balance' => '1200.50',  'daily_limit' => '800.00',  'monthly_limit' => '8000.00'],
            ],
            'c2@gmail.com' => [
                ['type' => 'savings',  'balance' => '15000.00', 'daily_limit' => null,      'monthly_limit' => '20000.00'],
                ['type' => 'checking', 'balance' => '300.00',   'daily_limit' => '200.00',  'monthly_limit' => '2000.00'],
            ],
            'c3@gmail.com' => [
                ['type' => 'checking', 'balance' => '50.00',    'daily_limit' => '50.00',   'monthly_limit' => '500.00'],
                ['type' => 'investment', 'balance' => '25000.00', 'daily_limit' => null,      'monthly_limit' => null],
            ],
        ];

        foreach ($plans as $email => $accounts) {
            $user = $customers->get($email);
            if (!$user) {
                continue;
            }

            $group = $this->upsertAccount(
                userId: (int) $user->id,
                parentId: null,
                type: 'group',
                balance: '0.00',
                dailyLimit: null,
                monthlyLimit: null
            );

            $sumChildren = '0.00';

            foreach ($accounts as $acc) {
                $child = $this->upsertAccount(
                    userId: (int) $user->id,
                    parentId: (int) $group->id,
                    type: $acc['type'],
                    balance: $acc['balance'],
                    dailyLimit: $acc['daily_limit'],
                    monthlyLimit: $acc['monthly_limit']
                );

                $sumChildren = bcadd($sumChildren, (string) $child->balance, 2);
            }

            $group->balance = $sumChildren;
            $group->save();

            $msg = "Seeded accounts for {$email}. GroupBalance={$sumChildren}";
            Log::info($msg);
        }
    }

    private function upsertAccount(
        int $userId,
        ?int $parentId,
        string $type,
        string $balance,
        ?string $dailyLimit,
        ?string $monthlyLimit
    ): AccountModel {
        $query = AccountModel::withTrashed()
            ->where('user_id', $userId)
            ->where('type', $type);

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        $model = $query->first();

        if (!$model) {
            $model = new AccountModel();
            $model->user_id = $userId;
            $model->parent_id = $parentId;
            $model->type = $type;
            $model->state = 'active';
        } else {
            // لو كان soft-deleted رجّعه
            if ($model->trashed()) {
                $model->restore();
            }
        }

        $model->balance = $balance;
        $model->daily_limit = $dailyLimit;
        $model->monthly_limit = $monthlyLimit;
        $model->state = $model->state ?? 'active';

        $model->save();

        return $model;
    }
}
