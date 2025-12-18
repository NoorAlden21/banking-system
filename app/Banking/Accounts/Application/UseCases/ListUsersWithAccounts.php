<?php

namespace App\Banking\Accounts\Application\UseCases;

use App\Models\User;
use App\Banking\Accounts\Infrastructure\Persistence\Models\AccountModel;

final class ListUsersWithAccounts
{
    public function handle(
        int $limit = 50,
        int $page = 1,
        bool $includeGroup = false,
        bool $onlyCustomers = true,
        ?string $search = null
    ): array {
        $limit = max(1, min($limit, 200));
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $q = User::query()
            ->select(['id', 'public_id', 'name', 'email', 'phone'])
            ->orderByDesc('id');

        if ($onlyCustomers) {
            $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['customer', 'Customer']));
        }

        if ($search !== null && trim($search) !== '') {
            $s = trim($search);
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('public_id', 'like', "%{$s}%");
            });
        }

        // pagination خفيف
        $users = $q->limit($limit)->offset($offset)->get();

        if ($users->isEmpty()) return [];

        $userIds = $users->pluck('id')->all();

        $accounts = AccountModel::query()
            ->select(['id', 'public_id', 'parent_id', 'user_id', 'type', 'state', 'balance', 'daily_limit', 'monthly_limit'])
            ->whereIn('user_id', $userIds)
            ->when(!$includeGroup, fn ($qq) => $qq->where('type', '!=', 'group'))
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');

        // parent_public_id mapping (اختياري للـUI)
        $parentIds = $accounts->flatten(1)->pluck('parent_id')->filter()->unique()->values()->all();
        $parentsMap = [];
        if ($parentIds) {
            $parentsMap = AccountModel::query()->whereIn('id', $parentIds)->pluck('public_id', 'id')->toArray();
        }

        // ارجع DTO بسيط array (بدون ReadModels)
        return $users->map(function ($u) use ($accounts, $parentsMap) {
            $accs = ($accounts->get($u->id) ?? collect())->map(function ($a) use ($parentsMap) {
                return [
                    'public_id' => (string) $a->public_id,
                    'parent_public_id' => $a->parent_id ? ($parentsMap[$a->parent_id] ?? null) : null,
                    'type' => (string) $a->type,
                    'state' => (string) $a->state,
                    'balance' => (string) $a->balance,
                    'daily_limit' => $a->daily_limit !== null ? (string) $a->daily_limit : null,
                    'monthly_limit' => $a->monthly_limit !== null ? (string) $a->monthly_limit : null,
                ];
            })->values()->all();

            return [
                'id' => (int) $u->id,
                'public_id' => (string) $u->public_id,
                'name' => (string) $u->name,
                'email' => (string) $u->email,
                'phone' => $u->phone !== null ? (string) $u->phone : null,
                'accounts' => $accs,
            ];
        })->values()->all();
    }
}
