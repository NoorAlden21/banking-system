<?php

namespace App\Banking\Accounts\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;

use App\Models\User;

use App\Banking\Shared\Domain\Contracts\AuditLogger;
use App\Banking\Shared\Application\DTOs\AuditEntryData;

use App\Banking\Accounts\Application\DTOs\OpenAccountData;
use App\Banking\Accounts\Application\DTOs\ChangeStateData;
use App\Banking\Accounts\Application\DTOs\CustomerData;
use App\Banking\Accounts\Application\DTOs\OnboardCustomerData;

use App\Banking\Accounts\Application\UseCases\ListMyAccounts;
use App\Banking\Accounts\Application\UseCases\OpenAccount;
use App\Banking\Accounts\Application\UseCases\GetMyAccountTree;
use App\Banking\Accounts\Application\UseCases\ChangeAccountState;
use App\Banking\Accounts\Application\UseCases\OnboardCustomerWithAccounts;
use App\Banking\Accounts\Application\UseCases\ListUsersWithAccounts;

use App\Banking\Accounts\Domain\Enums\AccountTypeEnum;

use App\Banking\Accounts\Presentation\Http\Requests\OpenAccountRequest;
use App\Banking\Accounts\Presentation\Http\Requests\ChangeStateRequest;
use App\Banking\Accounts\Presentation\Http\Requests\OnboardCustomerRequest;
use App\Banking\Accounts\Presentation\Http\Requests\ListUsersWithAccountsRequest;

use App\Banking\Accounts\Presentation\Http\Resources\AccountResource;
use App\Banking\Accounts\Presentation\Http\Resources\AccountTreeResource;

class AccountsController
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    private function actorRole(?User $user): string
    {
        if (!$user) return 'guest';
        if (method_exists($user, 'getRoleNames')) {
            return (string) ($user->getRoleNames()->first() ?? 'user');
        }
        return 'user';
    }

    private function safeAudit(
        ?User $actor,
        string $action,
        ?string $subjectType = null,
        ?string $subjectPublicId = null,
        array $meta = []
    ): void {
        try {
            $req = request();

            $this->audit->log(new AuditEntryData(
                actorUserId: (int) ($actor?->id ?? 0),
                actorRole: $this->actorRole($actor),
                action: $action,
                subjectType: $subjectType,
                subjectPublicId: $subjectPublicId,
                ip: $req?->ip(),
                userAgent: (string) ($req?->userAgent() ?? ''),
                meta: $meta,
            ));
        } catch (\Throwable $ignored) {
        }
    }

    private function errorMeta(\Throwable $e): array
    {
        return [
            'error' => class_basename($e),
            'message' => $e->getMessage(),
        ];
    }

    public function index(ListMyAccounts $useCase)
    {
        $userId = (int) auth()->id();
        $items = $useCase->handle($userId);
        return AccountResource::collection($items);
    }

    public function store(OpenAccountRequest $request, OpenAccount $useCase): JsonResponse
    {
        $actor = $request->user();

        $dto = new OpenAccountData(
            type: AccountTypeEnum::from($request->string('type')->toString()),
            dailyLimit: $request->filled('daily_limit') ? (string) $request->input('daily_limit') : null,
            monthlyLimit: $request->filled('monthly_limit') ? (string) $request->input('monthly_limit') : null,
        );

        $this->safeAudit($actor, 'accounts.open.attempt', 'user', (string) ($actor->public_id ?? ''), [
            'type' => $dto->type->value,
            'daily_limit' => $dto->dailyLimit,
            'monthly_limit' => $dto->monthlyLimit,
        ]);

        try {
            $account = $useCase->handle((int) $actor->id, $dto);

            $accountPublicId =
                $account->publicId ?? $account->publicId ?? null;

            $this->safeAudit($actor, 'accounts.open.success', 'account', $accountPublicId, [
                'type' => $dto->type->value,
            ]);

            return (new AccountResource($account))
                ->additional(['message' => 'تم إنشاء الحساب بنجاح'])
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $e) {
            $this->safeAudit($actor, 'accounts.open.failed', 'user', (string) ($actor->public_id ?? ''), array_merge([
                'type' => $dto->type->value,
            ], $this->errorMeta($e)));

            throw $e;
        }
    }

    public function onboard(OnboardCustomerRequest $request, OnboardCustomerWithAccounts $useCase): JsonResponse
    {
        $actor = $request->user();

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

        $this->safeAudit($actor, 'customers.onboard.attempt', 'customer', null, [
            'email' => $customer->email,
            'phone' => $customer->phone,
            'accounts_count' => count($accounts),
            'accounts_types' => array_map(fn ($x) => $x->type->value, $accounts),
        ]);

        try {
            $result = $useCase->handle(new OnboardCustomerData(
                customer: $customer,
                accounts: $accounts,
            ));

            $plainPassword = (string) ($result['plain_password'] ?? '');
            $shouldReveal = app()->environment(['local', 'testing']); // ✅ ALWAYS in local/testing


            /** @var \App\Models\User $user */
            $user = $result['user'];
            $opened = $result['accounts'];

            $this->safeAudit($actor, 'customers.onboard.success', 'customer', (string) $user->public_id, [
                'email' => (string) $user->email,
                'accounts_count' => count($opened),
            ]);

            return response()->json([
                'message' => 'تم إنشاء العميل وفتح الحسابات بنجاح (تم إرسال بيانات الدخول عبر البريد).',
                'customer' => [
                    'public_id' => $user->public_id,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'temporary_password' => $shouldReveal ? $plainPassword : null,
                ],
                'accounts' => AccountResource::collection($opened),
            ], 201);
        } catch (\Throwable $e) {
            $this->safeAudit($actor, 'customers.onboard.failed', 'customer', null, array_merge([
                'email' => $customer->email,
                'phone' => $customer->phone,
            ], $this->errorMeta($e)));

            throw $e;
        }
    }

    public function openForUser(int $userId, OpenAccountRequest $request, OpenAccount $useCase): JsonResponse
    {
        $actor = $request->user();

        $user = User::query()->find($userId);
        if (!$user) {
            $this->safeAudit($actor, 'accounts.open_for_user.failed', 'user', null, [
                'target_user_id' => $userId,
                'reason' => 'user_not_found',
            ]);

            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        $dto = new OpenAccountData(
            type: AccountTypeEnum::from($request->string('type')->toString()),
            dailyLimit: $request->filled('daily_limit') ? (string) $request->input('daily_limit') : null,
            monthlyLimit: $request->filled('monthly_limit') ? (string) $request->input('monthly_limit') : null,
        );

        $this->safeAudit($actor, 'accounts.open_for_user.attempt', 'user', (string) ($user->public_id ?? ''), [
            'target_user_id' => $userId,
            'type' => $dto->type->value,
        ]);

        try {
            $account = $useCase->handle($userId, $dto);

            $accountPublicId =
                $account->publicId ?? $account->publicId ?? null;

            $this->safeAudit($actor, 'accounts.open_for_user.success', 'account', $accountPublicId, [
                'target_user_id' => $userId,
                'target_user_public_id' => (string) ($user->public_id ?? ''),
                'type' => $dto->type->value,
            ]);

            return response()->json([
                'message' => 'تم فتح الحساب للمستخدم بنجاح',
                'data' => new AccountResource($account),
            ], 201);
        } catch (\Throwable $e) {
            $this->safeAudit($actor, 'accounts.open_for_user.failed', 'user', (string) ($user->public_id ?? ''), array_merge([
                'target_user_id' => $userId,
                'type' => $dto->type->value,
            ], $this->errorMeta($e)));

            throw $e;
        }
    }

    public function tree(GetMyAccountTree $useCase)
    {
        $userId = (int) auth()->id();
        $group = $useCase->handle($userId);
        return new AccountTreeResource($group);
    }

    public function changeState(string $publicId, ChangeStateRequest $request, ChangeAccountState $useCase)
    {
        $actor = $request->user();

        $dto = new ChangeStateData(
            targetState: $request->string('state')->toString()
        );

        $this->safeAudit($actor, 'accounts.change_state.attempt', 'account', $publicId, [
            'target_state' => $dto->targetState,
        ]);

        try {
            $updated = $useCase->handle($publicId, $dto);

            $newState =
                $updated->state->value ?? $updated->state ?? null;

            $this->safeAudit($actor, 'accounts.change_state.success', 'account', $publicId, [
                'target_state' => $dto->targetState,
                'new_state' => $newState,
            ]);

            return (new AccountResource($updated))
                ->additional(['message' => 'تم تغيير حالة الحساب'])
                ->response();
        } catch (\Throwable $e) {
            $this->safeAudit($actor, 'accounts.change_state.failed', 'account', $publicId, array_merge([
                'target_state' => $dto->targetState,
            ], $this->errorMeta($e)));

            throw $e;
        }
    }

    public function usersWithAccounts(
        ListUsersWithAccountsRequest $request,
        ListUsersWithAccounts $useCase
    ): JsonResponse {
        $data = $useCase->handle(
            limit: $request->limit(),
            page: $request->page(),
            includeGroup: $request->includeGroup(),
            onlyCustomers: $request->onlyCustomers(),
            search: $request->search()
        );

        return response()->json([
            'data' => $data,
            'meta' => [
                'limit' => $request->limit(),
                'page' => $request->page(),
            ],
        ]);
    }
}
