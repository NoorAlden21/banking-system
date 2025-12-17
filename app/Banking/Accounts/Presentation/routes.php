<?php

use Illuminate\Support\Facades\Route;
use App\Banking\Accounts\Presentation\Http\Controllers\AccountsController;

Route::middleware(['auth:sanctum'])->prefix('accounts')->group(function () {
    Route::get('/', [AccountsController::class, 'index']);
    Route::get('/tree', [AccountsController::class, 'tree']);

    // Onboarding: create customer + open multiple accounts
    Route::post('/onboard', [AccountsController::class, 'onboard'])
        ->middleware(['permission:customers.create', 'permission:accounts.open']);

    // open extra account for an existing user (optional if you already added it)
    Route::post('/users/{userId}', [AccountsController::class, 'openForUser'])
        ->middleware('permission:accounts.open');

    Route::patch('/{publicId}/state', [AccountsController::class, 'changeState'])
        ->middleware('permission:accounts.change-state');
});
