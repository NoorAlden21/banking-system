<?php

namespace App\Banking\Admin\Infrastructure\Persistence\Repositories;

use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Banking\Accounts\Infrastructure\Persistence\Models\AccountModel;
use App\Banking\Transactions\Infrastructure\Persistence\Models\TransactionModel;
use App\Banking\Transactions\Infrastructure\Persistence\Models\ScheduledTransactionModel;

use App\Banking\Admin\Domain\Contracts\MetricsReadRepository;

final class EloquentMetricsReadRepository implements MetricsReadRepository
{
    public function dashboard(): array
    {
        $today = now()->toDateString();

        $users = User::query()->count();
        $accounts = AccountModel::query()->count();
        $transactionsToday = TransactionModel::query()
            ->whereDate('created_at', $today)
            ->count();

        $pendingApprovals = TransactionModel::query()
            ->where('status', 'pending_approval')
            ->count();

        $scheduledActive = ScheduledTransactionModel::query()
            ->where('status', 'active')
            ->count();

        $scheduledDueNow = ScheduledTransactionModel::query()
            ->where('status', 'active')
            ->where('next_run_at', '<=', now())
            ->whereNull('locked_at')
            ->count();

        $moneyInSystem = AccountModel::query()
            ->where('type', '!=', 'group')
            ->sum('balance');

        return [
            'users' => $users,
            'accounts' => $accounts,
            'transactions_today' => $transactionsToday,
            'pending_approvals' => $pendingApprovals,
            'scheduled_active' => $scheduledActive,
            'scheduled_due_now' => $scheduledDueNow,
            'money_in_system' => (string) $moneyInSystem,
        ];
    }
}
