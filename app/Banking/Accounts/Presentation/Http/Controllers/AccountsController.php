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

    public function tree(GetMyAccountTree $useCase)
    {
        $userId = (int) auth()->id();
        $group = $useCase->handle($userId);

        return new AccountTreeResource($group);
    }
}
