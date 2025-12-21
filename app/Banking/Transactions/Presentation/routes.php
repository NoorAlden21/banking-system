<?php

use App\Banking\Transactions\Presentation\Http\Controllers\ScheduledTransactionsController;
use Illuminate\Support\Facades\Route;
use App\Banking\Transactions\Presentation\Http\Controllers\TransactionsController;

Route::middleware(['auth:sanctum'])->prefix('transactions')->group(function () {

    Route::prefix('scheduled')->group(function () {

        Route::get('/', [ScheduledTransactionsController::class, 'index'])
            ->middleware('permission:scheduled-transactions.view');

        Route::post('/', [ScheduledTransactionsController::class, 'store'])
            ->middleware('permission:scheduled-transactions.create');

        Route::get('/{publicId}', [ScheduledTransactionsController::class, 'show'])
            ->whereUuid('publicId')
            ->middleware('permission:scheduled-transactions.view');

        Route::patch('/{publicId}', [ScheduledTransactionsController::class, 'update'])
            ->whereUuid('publicId')
            ->middleware('permission:scheduled-transactions.update');

        Route::delete('/{publicId}', [ScheduledTransactionsController::class, 'destroy'])
            ->whereUuid('publicId')
            ->middleware('permission:scheduled-transactions.delete');
    });

    Route::get('/', [TransactionsController::class, 'index'])
        ->middleware('permission:transactions.view');

    Route::get('/pending-approvals', [TransactionsController::class, 'pendingApprovals'])
        ->middleware('permission:transactions.approve');

    // ✅ constrain publicId as UUID
    Route::get('/{publicId}', [TransactionsController::class, 'show'])
        ->whereUuid('publicId')
        ->middleware('permission:transactions.view');

    Route::post('/{publicId}/decision', [TransactionsController::class, 'decision'])
        ->whereUuid('publicId')
        ->middleware('permission:transactions.approve');

    Route::post('/deposit',  [TransactionsController::class, 'deposit'])
        ->middleware('permission:transactions.deposit');

    Route::post('/withdraw', [TransactionsController::class, 'withdraw'])
        ->middleware('permission:transactions.withdraw');

    Route::post('/transfer', [TransactionsController::class, 'transfer'])
        ->middleware('permission:transactions.transfer');
});
