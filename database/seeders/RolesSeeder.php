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

        $admin    = Role::findOrCreate('admin');
        $manager  = Role::findOrCreate('manager');
        $teller   = Role::findOrCreate('teller');
        $customer = Role::findOrCreate('customer');

        // Permissions
        $customersCreate = Permission::findOrCreate('customers.create');
        $accountsOpen    = Permission::findOrCreate('accounts.open');
        $changeState     = Permission::findOrCreate('accounts.change-state');

        // Staff permissions
        $admin->givePermissionTo([$customersCreate, $accountsOpen, $changeState]);
        $manager->givePermissionTo([$customersCreate, $accountsOpen, $changeState]);
        $teller->givePermissionTo([$customersCreate, $accountsOpen]);

        // customer لا شيء هنا
        // (اختياري) لو لاحقًا بدك teller يقدر يعمل freeze فقط، بنعمل permission أخرى
        // Permission::findOrCreate('accounts.freeze');
        // Permission::findOrCreate('accounts.close');
    }
}
