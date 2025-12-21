<?php

namespace App\Banking\Transactions\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;

use App\Banking\Transactions\Presentation\Http\Requests\ListScheduledTransactionsRequest;
use App\Banking\Transactions\Presentation\Http\Requests\CreateScheduledTransactionRequest;
use App\Banking\Transactions\Presentation\Http\Requests\UpdateScheduledTransactionRequest;

use App\Banking\Transactions\Presentation\Http\Resources\ScheduledTransactionResource;

use App\Banking\Transactions\Application\DTOs\CreateScheduledTransactionData;
use App\Banking\Transactions\Application\DTOs\UpdateScheduledTransactionData;

use App\Banking\Transactions\Application\UseCases\CreateScheduledTransaction;
use App\Banking\Transactions\Application\UseCases\UpdateScheduledTransaction;
use App\Banking\Transactions\Application\UseCases\CancelScheduledTransaction;
use App\Banking\Transactions\Application\UseCases\ListScheduledTransactions;
use App\Banking\Transactions\Application\UseCases\ShowScheduledTransaction;

final class ScheduledTransactionsController
{
    public function index(ListScheduledTransactionsRequest $request, ListScheduledTransactions $useCase): JsonResponse
    {
        $user = $request->user();
        $canViewAll = $user->can('scheduled-transactions.view-all');

        $scope = $request->filled('scope') ? $request->scope() : ($canViewAll ? 'all' : 'mine');

        $result = $useCase->handle(
            actorUserId: (int) $user->id,
            canViewAll: $canViewAll,
            scope: $scope,
            filters: $request->filters(),
            perPage: $request->perPage(),
            page: $request->page(),
        );

        return response()->json([
            'data' => ScheduledTransactionResource::collection($result['data']),
            'meta' => $result['meta'],
        ]);
    }

    public function show(string $publicId, ListScheduledTransactionsRequest $request, ShowScheduledTransaction $useCase): JsonResponse
    {
        $user = $request->user();
        $canViewAll = $user->can('scheduled-transactions.view-all');

        $scope = $request->filled('scope') ? $request->scope() : ($canViewAll ? 'all' : 'mine');

        $detail = $useCase->handle(
            actorUserId: (int) $user->id,
            canViewAll: $canViewAll,
            scope: $scope,
            publicId: $publicId
        );

        if (!$detail) return response()->json(['message' => 'غير موجود'], 404);

        return response()->json(['data' => new ScheduledTransactionResource($detail)]);
    }

    public function store(CreateScheduledTransactionRequest $request, CreateScheduledTransaction $useCase): JsonResponse
    {
        $user = $request->user();
        $canOperateAny = $user->can('scheduled-transactions.manage-any');

        $ownerUserId = $request->filled('owner_user_id')
            ? (int) $request->input('owner_user_id')
            : (int) $user->id;

        $dto = new CreateScheduledTransactionData(
            ownerUserId: $ownerUserId,
            createdByUserId: (int) $user->id,

            sourceAccountPublicId: $request->string('source_account_public_id')->toString(),
            destinationAccountPublicId: $request->string('destination_account_public_id')->toString(),
            amount: (string) $request->input('amount'),
            description: $request->filled('description') ? $request->string('description')->toString() : null,

            frequency: $request->string('frequency')->toString(),
            interval: (int) ($request->input('interval', 1)),
            dayOfWeek: $request->filled('day_of_week') ? (int) $request->input('day_of_week') : null,
            dayOfMonth: $request->filled('day_of_month') ? (int) $request->input('day_of_month') : null,
            runTime: $request->runTime(),

            startAt: $request->filled('start_at') ? (string) $request->input('start_at') : null,
            endAt: $request->filled('end_at') ? (string) $request->input('end_at') : null,
        );

        $res = $useCase->handle((int) $user->id, $canOperateAny, $dto);

        return response()->json($res, 201);
    }

    public function update(string $publicId, UpdateScheduledTransactionRequest $request, UpdateScheduledTransaction $useCase): JsonResponse
    {
        $user = $request->user();
        $canOperateAny = $user->can('scheduled-transactions.manage-any');

        $dto = new UpdateScheduledTransactionData(
            amount: $request->filled('amount') ? (string) $request->input('amount') : null,
            description: $request->filled('description') ? $request->string('description')->toString() : null,

            frequency: $request->filled('frequency') ? $request->string('frequency')->toString() : null,
            interval: $request->filled('interval') ? (int) $request->input('interval') : null,
            dayOfWeek: $request->filled('day_of_week') ? (int) $request->input('day_of_week') : null,
            dayOfMonth: $request->filled('day_of_month') ? (int) $request->input('day_of_month') : null,
            runTime: $request->runTimeOrNull(),

            startAt: $request->filled('start_at') ? (string) $request->input('start_at') : null,
            endAt: $request->filled('end_at') ? (string) $request->input('end_at') : null,

            status: $request->filled('status') ? $request->string('status')->toString() : null,
        );

        $res = $useCase->handle((int) $user->id, $canOperateAny, $publicId, $dto);

        return response()->json($res, 200);
    }

    public function destroy(string $publicId, ListScheduledTransactionsRequest $request, CancelScheduledTransaction $useCase): JsonResponse
    {
        $user = $request->user();
        $canOperateAny = $user->can('scheduled-transactions.manage-any');

        $res = $useCase->handle((int) $user->id, $canOperateAny, $publicId);

        return response()->json($res, 200);
    }
}
