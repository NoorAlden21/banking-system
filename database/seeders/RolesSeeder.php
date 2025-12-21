<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Roles
        $admin    = Role::findOrCreate('admin');
        $manager  = Role::findOrCreate('manager');
        $teller   = Role::findOrCreate('teller');
        $customer = Role::findOrCreate('customer');

        /**
         * =========================
         * Accounts / Customers
         * =========================
         */
        $customersCreate     = Permission::findOrCreate('customers.create');
        $accountsOpen        = Permission::findOrCreate('accounts.open');
        $accountsChangeState = Permission::findOrCreate('accounts.change-state');
        $accountsViewAll     = Permission::findOrCreate('accounts.view-all');

        /**
         * =========================
         * Transactions
         * =========================
         */
        $transactionsView       = Permission::findOrCreate('transactions.view');
        $transactionsViewAll    = Permission::findOrCreate('transactions.view-all');
        $transactionsApprove    = Permission::findOrCreate('transactions.approve');
        $transactionsOperateAny = Permission::findOrCreate('transactions.operate-any');

        $transactionsDeposit    = Permission::findOrCreate('transactions.deposit');
        $transactionsWithdraw   = Permission::findOrCreate('transactions.withdraw');
        $transactionsTransfer   = Permission::findOrCreate('transactions.transfer');

        $transactionsDepositExternal = Permission::findOrCreate('transactions.deposit-external');

        /**
         * =========================
         * Scheduled Transactions
         * =========================
         */
        $schView       = Permission::findOrCreate('scheduled-transactions.view');
        $schCreate     = Permission::findOrCreate('scheduled-transactions.create');
        $schUpdate     = Permission::findOrCreate('scheduled-transactions.update');
        $schDelete     = Permission::findOrCreate('scheduled-transactions.delete');
        $schViewAll    = Permission::findOrCreate('scheduled-transactions.view-all');
        $schManageAny  = Permission::findOrCreate('scheduled-transactions.manage-any'); // ✅ المهمّة

        /**
         * =========================
         * Reports / Audit Logs
         * =========================
         */
        $adminDashboard = Permission::findOrCreate('admin.dashboard.view');
        $reportsView    = Permission::findOrCreate('reports.view');
        $auditView      = Permission::findOrCreate('audit.view');
        /**
         * =========================
         * Assign permissions
         * =========================
         * ملاحظة: استخدمت syncPermissions عشان يبقى seeder idempotent ومش يراكم.
         */

        // Admin: full access
        $admin->syncPermissions([
            $customersCreate,
            $accountsOpen,
            $accountsChangeState,
            $accountsViewAll,

            $transactionsView,
            $transactionsViewAll,
            $transactionsApprove,
            $transactionsOperateAny,
            $transactionsDeposit,
            $transactionsWithdraw,
            $transactionsTransfer,
            $transactionsDepositExternal,

            $schView,
            $schCreate,
            $schUpdate,
            $schDelete,
            $schViewAll,
            $schManageAny,

            $adminDashboard,
            $reportsView,
            $auditView,
        ]);

        // Manager: full access + approvals
        $manager->syncPermissions([
            $customersCreate,
            $accountsOpen,
            $accountsChangeState,
            $accountsViewAll,

            $transactionsView,
            $transactionsViewAll,
            $transactionsApprove,
            $transactionsOperateAny,
            $transactionsDeposit,
            $transactionsWithdraw,
            $transactionsTransfer,
            $transactionsDepositExternal,

            $schView,
            $schCreate,
            $schUpdate,
            $schDelete,
            $schViewAll,
            $schManageAny,

            $adminDashboard,
            $reportsView,

        ]);

        // Teller: عمليات مالية + scheduled (اختياري حسب نظامكم)
        $teller->syncPermissions([
            $customersCreate,
            $accountsOpen,

            $transactionsView,
            $transactionsOperateAny,
            $transactionsDeposit,
            $transactionsWithdraw,
            $transactionsTransfer,
            $transactionsDepositExternal,

            $schView,
            $schCreate,
            $schUpdate,
            $schDelete,

            // لو teller لازم يقدر يدير أي scheduled لعميل:
            // $schManageAny,
            // $schViewAll,
        ]);

        // Customer: يدير scheduled الخاصة به فقط (بدون view-all / manage-any)
        $customer->syncPermissions([
            $transactionsView,
            $transactionsWithdraw,
            $transactionsTransfer,
            $transactionsDepositExternal,

            $schView,
            $schCreate,
            $schUpdate,
            $schDelete,
        ]);
    }
}
