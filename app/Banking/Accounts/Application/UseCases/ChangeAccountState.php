<?php

namespace App\Banking\Accounts\Application\UseCases;

use App\Banking\Accounts\Application\DTOs\ChangeStateData;
use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Domain\Exceptions\InvalidAccountStateTransition;
use App\Banking\Accounts\Domain\Patterns\State\AccountStateFactory;
use Illuminate\Support\Facades\DB;

final class ChangeAccountState
{
    public function __construct(private readonly AccountRepository $accounts)
    {
    }

    public function handle(string $accountPublicId, ChangeStateData $data)
    {
        return DB::transaction(function () use ($accountPublicId, $data) {

            $account = $this->accounts->findByPublicId($accountPublicId);

            if (!$account) {
                throw new \RuntimeException('الحساب غير موجود');
            }

            if ($account->isGroup()) {
                throw new \RuntimeException('لا يمكن تغيير حالة حساب group مباشرة');
            }

            $currentState = AccountStateFactory::from($account->state->value);
            $targetState = $data->targetState;

            if (!$currentState->canTransitionTo($targetState)) {
                throw new InvalidAccountStateTransition(
                    $currentState->transitionError($targetState)
                );
            }

            return $this->accounts->updateStateByPublicId($accountPublicId, $targetState);
        });
    }
}
