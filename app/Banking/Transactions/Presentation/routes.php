<?php

use Illuminate\Support\Facades\Route;
use App\Banking\Transactions\Presentation\Http\Controllers\TransactionsController;

Route::middleware(['auth:sanctum'])->prefix('transactions')->group(function () {

    // عمليات مالية (Idempotency-Key REQUIRED)
    Route::post('/deposit',  [TransactionsController::class, 'deposit'])
        ->middleware('permission:transactions.deposit');

    Route::post('/withdraw', [TransactionsController::class, 'withdraw'])
        ->middleware('permission:transactions.withdraw');

    Route::post('/transfer', [TransactionsController::class, 'transfer'])
        ->middleware('permission:transactions.transfer');

    // لاحقًا:
    // Route::get('/', [TransactionsController::class, 'index'])->middleware('permission:transactions.view');
    // Route::get('/{publicId}', [TransactionsController::class, 'show'])->middleware('permission:transactions.view');
});
