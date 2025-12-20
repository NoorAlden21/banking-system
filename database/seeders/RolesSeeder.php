<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
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
        $customersCreate   = Permission::findOrCreate('customers.create');
        $accountsOpen      = Permission::findOrCreate('accounts.open');
        $accountsChangeState = Permission::findOrCreate('accounts.change-state');
        $accountsViewAll   = Permission::findOrCreate('accounts.view-all');

        /**
         * =========================
         * Transactions
         * =========================
         */
        $transactionsView     = Permission::findOrCreate('transactions.view');      // index/show
        $transactionsViewAll  = Permission::findOrCreate('transactions.view-all');  // scope=all
        $transactionsApprove  = Permission::findOrCreate('transactions.approve');   // pending + decision
        $transactionsOperateAny = Permission::findOrCreate('transactions.operate-any'); // staff can withdraw/transfer from any account

        $transactionsDeposit  = Permission::findOrCreate('transactions.deposit');
        $transactionsWithdraw = Permission::findOrCreate('transactions.withdraw');
        $transactionsTransfer = Permission::findOrCreate('transactions.transfer');

        /**
         * =========================
         * Assign permissions
         * =========================
         */

        // Admin: full access
        $admin->givePermissionTo([
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
        ]);

        // Manager: full access + approvals
        $manager->givePermissionTo([
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
        ]);

        // Teller: عمليات مالية + (اختياري) view، لكن بدون view-all وبدون approvals
        $teller->givePermissionTo([
            $customersCreate,
            $accountsOpen,

            $transactionsView,
            $transactionsOperateAny,
            $transactionsDeposit,
            $transactionsWithdraw,
            $transactionsTransfer,
        ]);

        // Customer: يشوف معاملاته فقط + يسحب/يحوّل من حساباته فقط
        // (ownership يتم enforced في TransactionProcessor وليس بالpermission)
        $customer->givePermissionTo([
            $transactionsView,
            $transactionsWithdraw,
            $transactionsTransfer,
        ]);

        // لو قررت لاحقًا تخلي العميل يعمل deposit لنفسه:
        // $customer->givePermissionTo([$transactionsDeposit]);
    }
}
