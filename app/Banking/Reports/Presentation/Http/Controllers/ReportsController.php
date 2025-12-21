<?php

namespace App\Banking\Reports\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;

use App\Banking\Reports\Application\UseCases\GenerateDailyTransactionsReport;
use App\Banking\Reports\Application\UseCases\GenerateAccountSummariesReport;
use App\Banking\Reports\Application\UseCases\GenerateAuditLogsReport;

use App\Banking\Reports\Presentation\Http\Requests\DailyTransactionsReportRequest;
use App\Banking\Reports\Presentation\Http\Requests\AuditLogsReportRequest;
use Illuminate\Http\Request;

final class ReportsController
{
    public function dailyTransactions(DailyTransactionsReportRequest $request, GenerateDailyTransactionsReport $useCase): JsonResponse
    {
        return response()->json([
            'data' => $useCase->handle($request->reportDate()),
        ]);
    }

    public function accountSummaries(Request $request, GenerateAccountSummariesReport $useCase): JsonResponse
    {
        $perPage = (int) ($request->input('per_page', 50));
        $page = (int) ($request->input('page', 1));

        return response()->json($useCase->handle($perPage, $page));
    }

    public function auditLogs(AuditLogsReportRequest $request, GenerateAuditLogsReport $useCase): JsonResponse
    {
        return response()->json($useCase->handle($request->filters(), $request->perPage(), $request->page()));
    }
}
