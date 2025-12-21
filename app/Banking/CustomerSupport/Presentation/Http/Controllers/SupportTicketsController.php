<?php

namespace App\Banking\CustomerSupport\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;

use App\Banking\CustomerSupport\Presentation\Http\Requests\ListSupportTicketsRequest;
use App\Banking\CustomerSupport\Presentation\Http\Requests\CreateSupportTicketRequest;
use App\Banking\CustomerSupport\Presentation\Http\Requests\AddTicketMessageRequest;
use App\Banking\CustomerSupport\Presentation\Http\Requests\AssignTicketRequest;
use App\Banking\CustomerSupport\Presentation\Http\Requests\ChangeTicketStatusRequest;

use App\Banking\CustomerSupport\Presentation\Http\Resources\SupportTicketResource;

use App\Banking\CustomerSupport\Application\DTOs\CreateSupportTicketData;
use App\Banking\CustomerSupport\Application\DTOs\AddSupportMessageData;
use App\Banking\CustomerSupport\Application\DTOs\AssignSupportTicketData;
use App\Banking\CustomerSupport\Application\DTOs\ChangeSupportTicketStatusData;

use App\Banking\CustomerSupport\Application\UseCases\CreateSupportTicket;
use App\Banking\CustomerSupport\Application\UseCases\AddSupportTicketMessage;
use App\Banking\CustomerSupport\Application\UseCases\AssignSupportTicket;
use App\Banking\CustomerSupport\Application\UseCases\ChangeSupportTicketStatus;
use App\Banking\CustomerSupport\Application\UseCases\DeleteSupportTicket;
use App\Banking\CustomerSupport\Application\UseCases\ListSupportTickets;
use App\Banking\CustomerSupport\Application\UseCases\ShowSupportTicket;

final class SupportTicketsController
{
    public function index(ListSupportTicketsRequest $request, ListSupportTickets $useCase): JsonResponse
    {
        $user = $request->user();

        $canViewAll = $user->can('support.tickets.view-all');
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
            'data' => SupportTicketResource::collection($result['data']),
            'meta' => $result['meta'],
        ]);
    }

    public function show(string $publicId, ListSupportTicketsRequest $request, ShowSupportTicket $useCase): JsonResponse
    {
        $user = $request->user();

        $canViewAll = $user->can('support.tickets.view-all');
        $canSeeInternal = $user->can('support.tickets.internal-note');
        $scope = $request->filled('scope') ? $request->scope() : ($canViewAll ? 'all' : 'mine');

        $ticket = $useCase->handle(
            actorUserId: (int) $user->id,
            canViewAll: $canViewAll,
            scope: $scope,
            publicId: $publicId,
            canSeeInternal: $canSeeInternal,
        );

        if (!$ticket) return response()->json(['message' => 'غير موجود'], 404);

        return response()->json(['data' => new SupportTicketResource($ticket)]);
    }

    public function store(CreateSupportTicketRequest $request, CreateSupportTicket $useCase): JsonResponse
    {
        $user = $request->user();

        $dto = new CreateSupportTicketData(
            subject: $request->string('subject')->toString(),
            messageBody: $request->string('message')->toString(),
            category: $request->filled('category') ? $request->string('category')->toString() : null,
            priority: $request->priority(),
        );

        $res = $useCase->handle((int) $user->id, $dto);

        return response()->json($res, 201);
    }

    public function addMessage(string $publicId, AddTicketMessageRequest $request, AddSupportTicketMessage $useCase): JsonResponse
    {
        $user = $request->user();

        $canViewAll = $user->can('support.tickets.view-all');
        $canWriteInternal = $user->can('support.tickets.internal-note');

        $dto = new AddSupportMessageData(
            body: $request->string('body')->toString(),
            isInternal: $request->isInternal(),
        );

        $res = $useCase->handle(
            actorUserId: (int) $user->id,
            canViewAll: $canViewAll,
            canWriteInternal: $canWriteInternal,
            ticketPublicId: $publicId,
            data: $dto
        );

        return response()->json($res, 201);
    }

    public function assign(string $publicId, AssignTicketRequest $request, AssignSupportTicket $useCase): JsonResponse
    {
        $user = $request->user();

        $dto = new AssignSupportTicketData(
            assignedToUserId: $request->assignedToUserId(),
        );

        $res = $useCase->handle((int) $user->id, $publicId, $dto);

        return response()->json($res, 200);
    }

    public function changeStatus(string $publicId, ChangeTicketStatusRequest $request, ChangeSupportTicketStatus $useCase): JsonResponse
    {
        $user = $request->user();
        $canViewAll = $user->can('support.tickets.view-all');

        $dto = new ChangeSupportTicketStatusData(status: $request->status());

        $res = $useCase->handle((int) $user->id, $canViewAll, $publicId, $dto);

        return response()->json($res, 200);
    }

    public function destroy(string $publicId, DeleteSupportTicket $useCase): JsonResponse
    {
        $res = $useCase->handle($publicId);
        return response()->json($res, 200);
    }
}
