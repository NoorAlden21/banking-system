<?php

namespace App\Banking\Accounts\Presentation\Http\Controllers;

use App\Banking\Accounts\Application\DTOs\OpenAccountData;
use App\Banking\Accounts\Application\UseCases\ListMyAccounts;
use App\Banking\Accounts\Application\UseCases\OpenAccount;
use App\Banking\Accounts\Domain\Enums\AccountTypeEnum;
use App\Banking\Accounts\Presentation\Http\Requests\OpenAccountRequest;
use App\Banking\Accounts\Presentation\Http\Resources\AccountResource;
use Illuminate\Http\JsonResponse;
use App\Banking\Accounts\Application\UseCases\GetMyAccountTree;
use App\Banking\Accounts\Presentation\Http\Resources\AccountTreeResource;
use App\Banking\Accounts\Application\DTOs\ChangeStateData;
use App\Banking\Accounts\Application\DTOs\CustomerData;
use App\Banking\Accounts\Application\DTOs\OnboardCustomerData;
use App\Banking\Accounts\Application\UseCases\ChangeAccountState;
use App\Banking\Accounts\Application\UseCases\OnboardCustomerWithAccounts;
use App\Banking\Accounts\Presentation\Http\Requests\ChangeStateRequest;
use App\Banking\Accounts\Presentation\Http\Requests\OnboardCustomerRequest;

class AccountsController
{
    public function index(ListMyAccounts $useCase)
    {
        $userId = (int) auth()->id();

        $items = $useCase->handle($userId);

        return AccountResource::collection($items);
    }

    public function store(OpenAccountRequest $request, OpenAccount $useCase): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $dto = new OpenAccountData(
            type: AccountTypeEnum::from($request->string('type')->toString()),
            dailyLimit: $request->filled('daily_limit') ? (string) $request->input('daily_limit') : null,
            monthlyLimit: $request->filled('monthly_limit') ? (string) $request->input('monthly_limit') : null,
        );

        $account = $useCase->handle($userId, $dto);

        return (new AccountResource($account))
            ->additional(['message' => 'تم إنشاء الحساب بنجاح'])
            ->response()
            ->setStatusCode(201);
    }

    public function onboard(OnboardCustomerRequest $request, OnboardCustomerWithAccounts $useCase): JsonResponse
    {
        $customer = new CustomerData(
            name: $request->input('customer.name'),
            email: $request->input('customer.email'),
            phone: $request->input('customer.phone'),
        );

        $accounts = collect($request->input('accounts'))
            ->map(function ($a) {
                return new OpenAccountData(
                    type: AccountTypeEnum::from($a['type']),
                    dailyLimit: array_key_exists('daily_limit', $a) ? (string) $a['daily_limit'] : null,
                    monthlyLimit: array_key_exists('monthly_limit', $a) ? (string) $a['monthly_limit'] : null,
                );
            })
            ->all();

        $result = $useCase->handle(new OnboardCustomerData(
            customer: $customer,
            accounts: $accounts,
        ));

        /** @var \App\Models\User $user */
        $user = $result['user'];
        $opened = $result['accounts'];

        return response()->json([
            'message' => 'تم إنشاء العميل وفتح الحسابات بنجاح (تم إرسال بيانات الدخول عبر البريد).',
            'customer' => [
                'public_id' => $user->public_id,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'accounts' => AccountResource::collection($opened),
        ], 201);
    }

    public function tree(GetMyAccountTree $useCase)
    {
        $userId = (int) auth()->id();
        $group = $useCase->handle($userId);

        return new AccountTreeResource($group);
    }

    public function changeState(string $publicId, ChangeStateRequest $request, ChangeAccountState $useCase)
    {
        $dto = new ChangeStateData(
            targetState: $request->string('state')->toString()
        );

        $updated = $useCase->handle($publicId, $dto);

        return (new AccountResource($updated))
            ->additional(['message' => 'تم تغيير حالة الحساب'])
            ->response();
    }
}
