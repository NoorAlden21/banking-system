<?php

namespace App\Banking\CustomerSupport\Infrastructure\Persistence\Repositories;

use App\Banking\CustomerSupport\Domain\Contracts\SupportTicketReadRepository;
use App\Banking\CustomerSupport\Infrastructure\Persistence\Models\SupportTicketModel;

final class EloquentSupportTicketReadRepository implements SupportTicketReadRepository
{
    public function paginate(
        int $actorUserId,
        bool $canViewAll,
        string $scope,
        array $filters,
        int $perPage,
        int $page
    ): array {
        $q = SupportTicketModel::query()
            ->with([
                'owner:id,public_id,name,email',
                'assignedTo:id,public_id,name,email',
            ]);

        $scope = $scope ?: 'mine';
        if (!($scope === 'all' && $canViewAll)) {
            $q->where('owner_user_id', $actorUserId);
        }

        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['category'])) $q->where('category', $filters['category']);
        if (!empty($filters['priority'])) $q->where('priority', $filters['priority']);
        if (!empty($filters['assigned_to_user_id'])) $q->where('assigned_to_user_id', (int) $filters['assigned_to_user_id']);

        if (!empty($filters['q'])) {
            $term = (string) $filters['q'];
            $q->where(function ($qq) use ($term) {
                $qq->where('subject', 'like', "%{$term}%")
                    ->orWhereHas('messages', function ($mq) use ($term) {
                        $mq->where('body', 'like', "%{$term}%");
                    });
            });
        }

        $q->orderByDesc('last_message_at')->orderByDesc('id');

        $p = $q->paginate(perPage: $perPage, page: $page);

        return [
            'data' => $p->items(),
            'meta' => [
                'page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
        ];
    }

    public function findDetail(
        int $actorUserId,
        bool $canViewAll,
        string $scope,
        string $publicId,
        bool $canSeeInternal
    ): mixed {
        $q = SupportTicketModel::query()
            ->where('public_id', $publicId)
            ->with([
                'owner:id,public_id,name,email',
                'assignedTo:id,public_id,name,email',
                'messages' => function ($mq) use ($canSeeInternal) {
                    if (!$canSeeInternal) {
                        $mq->where('is_internal', false);
                    }
                    $mq->orderBy('created_at');
                },
                'messages.sender:id,public_id,name,email',
            ]);

        if (!($scope === 'all' && $canViewAll)) {
            $q->where('owner_user_id', $actorUserId);
        }

        return $q->first();
    }
}
