<?php

namespace App\Banking\Admin\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;

use App\Banking\Admin\Application\UseCases\GetDashboardMetrics;

final class AdminController
{
    public function dashboard(GetDashboardMetrics $useCase): JsonResponse
    {
        return response()->json([
            'data' => $useCase->handle(),
        ]);
    }
}
