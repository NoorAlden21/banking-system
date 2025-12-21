<?php

use App\Banking\Transactions\Presentation\Http\Controllers\ScheduledTransactionsController;
use Illuminate\Support\Facades\Route;
use App\Banking\Transactions\Presentation\Http\Controllers\TransactionsController;

Route::middleware(['auth:sanctum'])->prefix('transactions')->group(function () {

    Route::get('/', [TransactionsController::class, 'index'])
        ->middleware('permission:transactions.view');

    // alias واضح للموافقات
    Route::get('/pending-approvals', [TransactionsController::class, 'pendingApprovals'])
        ->middleware('permission:transactions.approve');

    Route::get('/{publicId}', [TransactionsController::class, 'show'])
        ->middleware('permission:transactions.view');

    Route::post('/{publicId}/decision', [TransactionsController::class, 'decision'])
        ->middleware('permission:transactions.approve');

    // عمليات مالية (Idempotency-Key REQUIRED)
    Route::post('/deposit',  [TransactionsController::class, 'deposit'])
        ->middleware('permission:transactions.deposit');

    Route::post('/withdraw', [TransactionsController::class, 'withdraw'])
        ->middleware('permission:transactions.withdraw');

    Route::post('/transfer', [TransactionsController::class, 'transfer'])
        ->middleware('permission:transactions.transfer');

    Route::prefix('scheduled')->group(function () {

        Route::get('/', [ScheduledTransactionsController::class, 'index'])
            ->middleware('permission:scheduled-transactions.view');

        Route::post('/', [ScheduledTransactionsController::class, 'store'])
            ->middleware('permission:scheduled-transactions.create');

        Route::get('/{publicId}', [ScheduledTransactionsController::class, 'show'])
            ->middleware('permission:scheduled-transactions.view');

        Route::patch('/{publicId}', [ScheduledTransactionsController::class, 'update'])
            ->middleware('permission:scheduled-transactions.update');

        Route::delete('/{publicId}', [ScheduledTransactionsController::class, 'destroy'])
            ->middleware('permission:scheduled-transactions.delete');
    });
});
