<?php

namespace App\Banking\Accounts\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

use App\Models\User;

use App\Banking\Shared\Domain\Contracts\AuditLogger;
use App\Banking\Shared\Application\DTOs\AuditEntryData;

use App\Banking\Accounts\Presentation\Http\Requests\PreviewInterestRequest;
use App\Banking\Accounts\Presentation\Http\Requests\ApplyInterestRequest;

use App\Banking\Accounts\Application\DTOs\PreviewInterestData;
use App\Banking\Accounts\Application\DTOs\ApplyInterestData;

use App\Banking\Accounts\Application\UseCases\PreviewAccountInterest;
use App\Banking\Accounts\Application\UseCases\ApplyAccountInterest;

final class AccountInterestController
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {
    }

    private function actorRole(?User $user): string
    {
        if (!$user) return 'guest';
        if (method_exists($user, 'getRoleNames')) {
            return (string) ($user->getRoleNames()->first() ?? 'user');
        }
        return 'user';
    }

    private function safeAudit(?User $actor, string $action, ?string $subjectType = null, ?string $subjectPublicId = null, array $meta = []): void
    {
        try {
            $req = request();
            $this->audit->log(new AuditEntryData(
                actorUserId: (int) ($actor?->id ?? 0),
                actorRole: $this->actorRole($actor),
                action: $action,
                subjectType: (string) ($subjectType ?? ''),
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

    public function preview(string $publicId, PreviewInterestRequest $request, PreviewAccountInterest $useCase): JsonResponse
    {
        $user = $request->user();
        $canViewAll = $user->can('accounts.view-all');

        $this->safeAudit($user, 'accounts.interest.preview.attempt', 'account', $publicId, [
            'days' => $request->days(),
            'market' => $request->market(),
        ]);

        try {
            $dto = new PreviewInterestData(days: $request->days(), market: $request->market());

            $res = $useCase->handle(
                actorUserId: (int) $user->id,
                canViewAll: $canViewAll,
                accountPublicId: $publicId,
                data: $dto,
            );

            $this->safeAudit($user, 'accounts.interest.preview.success', 'account', $publicId, [
                'interest' => (string) ($res['data']['interest'] ?? ''),
                'market' => (string) ($res['data']['market'] ?? ''),
                'days' => (int) ($res['data']['days'] ?? 0),
            ]);

            return response()->json($res, 200);
        } catch (\Throwable $e) {
            $this->safeAudit($user, 'accounts.interest.preview.failed', 'account', $publicId, $this->errorMeta($e));
            throw $e;
        }
    }

    public function apply(string $publicId, ApplyInterestRequest $request, ApplyAccountInterest $useCase): JsonResponse
    {
        $user = $request->user();
        $canViewAll = $user->can('accounts.view-all');

        $this->safeAudit($user, 'accounts.interest.apply.attempt', 'account', $publicId, [
            'days' => $request->days(),
            'market' => $request->market(),
        ]);

        try {
            $dto = new ApplyInterestData(days: $request->days(), market: $request->market());

            $res = DB::transaction(function () use ($useCase, $user, $canViewAll, $publicId, $dto) {
                return $useCase->handle(
                    actorUserId: (int) $user->id,
                    canViewAll: $canViewAll,
                    accountPublicId: $publicId,
                    data: $dto,
                );
            });

            DB::afterCommit(function () use ($user, $publicId, $res) {
                $this->safeAudit($user, 'accounts.interest.apply.success', 'account', $publicId, [
                    'interest' => (string) ($res['interest'] ?? ''),
                    'transaction_public_id' => (string) ($res['transaction_public_id'] ?? ''),
                    'transaction_status' => (string) ($res['transaction_status'] ?? ''),
                ]);
            });

            return response()->json($res, 200);
        } catch (\Throwable $e) {
            $this->safeAudit($user, 'accounts.interest.apply.failed', 'account', $publicId, $this->errorMeta($e));
            throw $e;
        }
    }
}
