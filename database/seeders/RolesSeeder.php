<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        foreach (['Customer', 'Teller', 'Manager', 'Admin'] as $roleName) {
            Role::findOrCreate($roleName, $guard);
        }
    }
}
