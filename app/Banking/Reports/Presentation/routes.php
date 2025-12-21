<?php

use Illuminate\Support\Facades\Route;
use App\Banking\Reports\Presentation\Http\Controllers\ReportsController;

Route::middleware(['auth:sanctum', 'permission:reports.view'])
    ->prefix('reports')
    ->group(function () {
        Route::get('/daily-transactions', [ReportsController::class, 'dailyTransactions']);
        Route::get('/account-summaries', [ReportsController::class, 'accountSummaries']);

        Route::get('/audit-logs', [ReportsController::class, 'auditLogs'])
            ->middleware('permission:audit.view');
    });
