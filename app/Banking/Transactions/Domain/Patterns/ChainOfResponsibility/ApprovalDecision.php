<?php

namespace App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility;

use Illuminate\Support\Facades\Config;

use App\Banking\Transactions\Domain\Contracts\TransactionRepository;
use App\Banking\Transactions\Domain\Contracts\ApprovalRepository;

final class ApprovalDecision implements TxStep
{
    public function __construct(
        private readonly TransactionRepository $txRepo,
        private readonly ApprovalRepository $approvals,
    ) {
    }

    public function handle(TxContext $ctx, callable $next): array
    {
        if (!$ctx->isTransfer()) {
            return $next($ctx);
        }

        $threshold = (string) Config::get('banking.approvals.manager_threshold', '10000.00');
        $needsApproval = (bccomp($ctx->amount, $threshold, 2) === 1);

        if (!$needsApproval) {
            return $next($ctx);
        }

        // create pending tx + approval, then stop chain
        $tx = $this->txRepo->create([
            'initiator_user_id' => $ctx->initiatorUserId,
            'type' => 'transfer',
            'status' => 'pending_approval',
            'source_account_id' => $ctx->source?->id,
            'destination_account_id' => $ctx->dest?->id,
            'amount' => $ctx->amount,
            'currency' => $ctx->currency,
            'description' => $ctx->description,
            'posted_at' => null,
        ]);

        $this->approvals->createPending($tx->id, $ctx->initiatorUserId);

        $ctx->stopWith([
            'message' => 'تم إنشاء التحويل وهو بانتظار الموافقة',
            'transaction_public_id' => $tx->publicId,
            'status' => 'pending_approval',
        ]);

        return $ctx->outcome;
    }
}
