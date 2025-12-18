<?php

namespace App\Banking\Accounts\Application\UseCases;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Banking\Accounts\Application\DTOs\OnboardCustomerData;
use App\Banking\Accounts\Domain\Events\CustomerOnboarded;

final class OnboardCustomerWithAccounts
{
    public function __construct(
        private readonly OpenAccount $openAccount,
    ) {
    }

    public function handle(OnboardCustomerData $data): array
    {
        $plainPassword = (string) random_int(10000000, 99999999);

        return DB::transaction(function () use ($data, $plainPassword) {

            $user = User::create([
                'name' => $data->customer->name,
                'email' => $data->customer->email,
                'phone' => $data->customer->phone,
                'password' => Hash::make($plainPassword),
                'must_change_password' => true,
                'password_changed_at' => null,
            ]);

            $user->syncRoles(['customer']);

            $opened = [];
            foreach ($data->accounts as $accDto) {
                $opened[] = $this->openAccount->handle((int) $user->id, $accDto);
            }

            event(new CustomerOnboarded(
                userId: (int) $user->id,
                userPublicId: (string) $user->public_id,
                email: (string) $user->email,
                plainPassword: $plainPassword,
            ));

            return [
                'user' => $user,
                'accounts' => $opened,
            ];
        });
    }
}
