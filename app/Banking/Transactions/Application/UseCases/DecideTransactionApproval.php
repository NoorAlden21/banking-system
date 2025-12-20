<?php

namespace App\Banking\Transactions\Application\UseCases;

use Illuminate\Support\Facades\DB;

use App\Banking\Transactions\Domain\Contracts\TransactionRepository;
use App\Banking\Transactions\Domain\Contracts\ApprovalRepository;
use App\Banking\Transactions\Domain\Services\TransactionProcessor;

final class DecideTransactionApproval
{
    public function __construct(
        private readonly TransactionRepository $txRepo,
        private readonly ApprovalRepository $approvals,
        private readonly TransactionProcessor $processor,
    ) {
    }

    public function handle(string $txPublicId, int $managerUserId, string $decision, ?string $note = null): array
    {
        return DB::transaction(function () use ($txPublicId, $managerUserId, $decision, $note) {

            $tx = $this->txRepo->lockForPostingByPublicId($txPublicId);
            if (!$tx) throw new \RuntimeException('المعاملة غير موجودة');

            if ($tx->status !== 'pending_approval') {
                throw new \RuntimeException('هذه المعاملة ليست بانتظار الموافقة');
            }

            $approval = $this->approvals->lockPendingByTransactionId($tx->id);
            if (!$approval) throw new \RuntimeException('سجل الموافقة غير موجود');

            if ($decision === 'reject') {
                $this->approvals->markRejected($approval->id, $managerUserId, $note);
                $this->txRepo->markRejected($tx->id);

                return [
                    'message' => 'تم رفض المعاملة',
                    'transaction_public_id' => $tx->publicId,
                    'status' => 'rejected',
                ];
            }

            // approve: post to ledger + balances
            $outcome = $this->processor->postApprovedTransfer($managerUserId, $tx);

            $this->approvals->markApproved($approval->id, $managerUserId, $note);
            $this->txRepo->markPosted($tx->id);

            return array_merge([
                'message' => $outcome->message,
                'transaction_public_id' => $outcome->transactionPublicId,
                'status' => $outcome->status,
            ], $outcome->data);
        });
    }
}
