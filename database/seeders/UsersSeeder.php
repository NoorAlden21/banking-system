<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = 'password';

        $users = [
            ['name' => 'Admin User',   'email' => 'admin@gmail.com',   'role' => 'admin'],
            ['name' => 'Teller User',  'email' => 'teller@gmail.com',  'role' => 'teller'],
            ['name' => 'Manager User', 'email' => 'manager@gmail.com', 'role' => 'manager'],

            ['name' => 'Customer One',   'email' => 'c1@gmail.com', 'role' => 'customer'],
            ['name' => 'Customer Two',   'email' => 'c2@gmail.com', 'role' => 'customer'],
            ['name' => 'Customer Three', 'email' => 'c3@gmail.com', 'role' => 'customer'],
        ];

        Log::channel('tokens')->info("=== Dev Seed Tokens ===");
        Log::channel('tokens')->info("Password for all users: {$password}");

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make($password),
                ]
            );

            $user->syncRoles([$u['role']]);

            $token = $user->createToken('dev-seed')->plainTextToken;

            $line = "{$u['role']} | {$u['email']} | TOKEN: {$token}";

            Log::channel('tokens')->info($line);
        }
    }
}
