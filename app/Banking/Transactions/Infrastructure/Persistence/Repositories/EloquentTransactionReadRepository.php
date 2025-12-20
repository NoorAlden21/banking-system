<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Banking\Accounts\Infrastructure\Persistence\Models\AccountModel;
use App\Banking\Transactions\Domain\Contracts\TransactionReadRepository;
use App\Banking\Transactions\Infrastructure\Persistence\Models\TransactionModel;
use App\Banking\Transactions\Infrastructure\Persistence\Models\LedgerEntryModel;
use App\Banking\Transactions\Infrastructure\Persistence\Models\TransactionApprovalModel;

final class EloquentTransactionReadRepository implements TransactionReadRepository
{
    public function paginateForUser(int $userId, array $filters, int $perPage, int $page): array
    {
        $accountIds = AccountModel::query()
            ->where('user_id', $userId)
            ->where('type', '!=', 'group')
            ->pluck('id')
            ->all();

        $q = TransactionModel::query()
            ->select([
                'id', 'public_id', 'initiator_user_id', 'type', 'status',
                'source_account_id', 'destination_account_id',
                'amount', 'currency', 'description', 'posted_at', 'created_at'
            ])
            ->where(function ($w) use ($accountIds) {
                $w->whereIn('source_account_id', $accountIds)
                    ->orWhereIn('destination_account_id', $accountIds);
            });

        $this->applyFilters($q, $filters);

        $q->orderByDesc('id');

        return $this->paginate($q, $perPage, $page);
    }

    public function paginateAll(array $filters, int $perPage, int $page): array
    {
        $q = TransactionModel::query()
            ->select([
                'id', 'public_id', 'initiator_user_id', 'type', 'status',
                'source_account_id', 'destination_account_id',
                'amount', 'currency', 'description', 'posted_at', 'created_at'
            ]);

        $this->applyFilters($q, $filters);

        $q->orderByDesc('id');

        return $this->paginate($q, $perPage, $page);
    }

    public function findDetailForUser(string $publicId, int $userId): ?array
    {
        $accountIds = AccountModel::query()
            ->where('user_id', $userId)
            ->where('type', '!=', 'group')
            ->pluck('id')
            ->all();

        $tx = TransactionModel::query()
            ->where('public_id', $publicId)
            ->where(function ($w) use ($accountIds) {
                $w->whereIn('source_account_id', $accountIds)
                    ->orWhereIn('destination_account_id', $accountIds);
            })
            ->first();

        if (!$tx) return null;

        return $this->buildDetail($tx);
    }

    public function findDetail(string $publicId): ?array
    {
        $tx = TransactionModel::query()->where('public_id', $publicId)->first();
        if (!$tx) return null;

        return $this->buildDetail($tx);
    }

    private function buildDetail(TransactionModel $tx): array
    {
        $ledger = LedgerEntryModel::query()
            ->select([
                'ledger_entries.id',
                'ledger_entries.direction',
                'ledger_entries.amount',
                'ledger_entries.currency',
                'ledger_entries.balance_before',
                'ledger_entries.balance_after',
                'ledger_entries.created_at',
                'accounts.public_id as account_public_id',
            ])
            ->join('accounts', 'accounts.id', '=', 'ledger_entries.account_id')
            ->where('ledger_entries.transaction_id', $tx->id)
            ->orderBy('ledger_entries.id')
            ->get()
            ->map(fn ($r) => [
                'account_public_id' => (string) $r->account_public_id,
                'direction' => (string) $r->direction,
                'amount' => (string) $r->amount,
                'currency' => (string) $r->currency,
                'balance_before' => (string) $r->balance_before,
                'balance_after' => (string) $r->balance_after,
                'created_at' => (string) $r->created_at,
            ])->all();

        $approval = TransactionApprovalModel::query()
            ->where('transaction_id', $tx->id)
            ->first();

        return [
            'public_id' => (string) $tx->public_id,
            'type' => (string) $tx->type,
            'status' => (string) $tx->status,
            'amount' => (string) $tx->amount,
            'currency' => (string) $tx->currency,
            'description' => $tx->description,
            'posted_at' => $tx->posted_at ? (string) $tx->posted_at : null,
            'created_at' => (string) $tx->created_at,

            'source_account_id' => $tx->source_account_id,
            'destination_account_id' => $tx->destination_account_id,

            'ledger_entries' => $ledger,

            'approval' => $approval ? [
                'status' => (string) $approval->status,
                'requested_by_user_id' => (int) $approval->requested_by_user_id,
                'decided_by_user_id' => $approval->decided_by_user_id ? (int) $approval->decided_by_user_id : null,
                'decided_at' => $approval->decided_at ? (string) $approval->decided_at : null,
                'note' => $approval->note ?? null,
            ] : null,
        ];
    }

    private function applyFilters($q, array $filters): void
    {
        if (!empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (!empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }
        if (!empty($filters['from'])) {
            $q->where('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $q->where('created_at', '<=', $filters['to']);
        }
        if (!empty($filters['q'])) {
            $s = $filters['q'];
            $q->where('public_id', 'like', "%{$s}%");
        }
        if (!empty($filters['min_amount'])) {
            $q->where('amount', '>=', $filters['min_amount']);
        }
        if (!empty($filters['max_amount'])) {
            $q->where('amount', '<=', $filters['max_amount']);
        }

        // filter by account_public_id
        if (!empty($filters['account_public_id'])) {
            $accId = AccountModel::query()
                ->where('public_id', $filters['account_public_id'])
                ->value('id');

            if ($accId) {
                $q->where(function ($w) use ($accId) {
                    $w->where('source_account_id', $accId)
                        ->orWhere('destination_account_id', $accId);
                });
            } else {
                // لا نتائج
                $q->whereRaw('1=0');
            }
        }
    }

    private function paginate($q, int $perPage, int $page): array
    {
        $perPage = max(1, min($perPage, 200));
        $page = max(1, $page);

        $total = (clone $q)->count();
        $items = $q->forPage($page, $perPage)->get()->map(fn ($t) => [
            'public_id' => (string) $t->public_id,
            'type' => (string) $t->type,
            'status' => (string) $t->status,
            'amount' => (string) $t->amount,
            'currency' => (string) $t->currency,
            'description' => $t->description,
            'posted_at' => $t->posted_at ? (string) $t->posted_at : null,
            'created_at' => (string) $t->created_at,
            'source_account_id' => $t->source_account_id,
            'destination_account_id' => $t->destination_account_id,
            'initiator_user_id' => (int) $t->initiator_user_id,
        ])->all();

        return [
            'items' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => (int) ceil($total / $perPage),
            ],
        ];
    }
}
