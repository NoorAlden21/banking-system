<?php

namespace App\Banking\Accounts\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;

use App\Banking\Accounts\Application\UseCases\ListAccountFeatures;
use App\Banking\Accounts\Application\UseCases\EnableAccountFeature;
use App\Banking\Accounts\Application\UseCases\DisableAccountFeature;
use App\Banking\Accounts\Application\UseCases\GetAccountCapabilities;

use App\Banking\Accounts\Presentation\Http\Requests\EnableAccountFeatureRequest;

final class AccountFeaturesController
{
    public function index(string $publicId, ListAccountFeatures $useCase): JsonResponse
    {
        $user = request()->user();
        $canViewAll = $user->can('accounts.view-all');

        $data = $useCase->handle((int) $user->id, $canViewAll, $publicId);

        return response()->json(['data' => $data]);
    }

    public function store(string $publicId, EnableAccountFeatureRequest $request, EnableAccountFeature $useCase): JsonResponse
    {
        $user = $request->user();

        $canManageAny = $user->can('accounts.features.manage-any');

        $res = $useCase->handle(
            actorUserId: (int) $user->id,
            canManageAny: $canManageAny,
            accountPublicId: $publicId,
            featureKey: $request->featureKey(),
            meta: $request->meta(),
        );

        return response()->json($res, 201);
    }

    public function destroy(string $publicId, string $featureKey, DisableAccountFeature $useCase): JsonResponse
    {
        $user = request()->user();
        $canManageAny = $user->can('accounts.features.manage-any');

        $res = $useCase->handle((int) $user->id, $canManageAny, $publicId, $featureKey);

        return response()->json($res, 200);
    }

    public function capabilities(string $publicId, GetAccountCapabilities $useCase): JsonResponse
    {
        $user = request()->user();
        $canViewAll = $user->can('accounts.view-all');

        $data = $useCase->handle((int) $user->id, $canViewAll, $publicId);

        return response()->json(['data' => $data]);
    }
}
