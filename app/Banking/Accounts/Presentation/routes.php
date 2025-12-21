<?php

use App\Banking\Accounts\Presentation\Http\Controllers\AccountFeaturesController;
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

    Route::get('/admin/users-with-accounts', [AccountsController::class, 'usersWithAccounts'])
        ->middleware('permission:accounts.view-all');

    Route::patch('/{publicId}/state', [AccountsController::class, 'changeState'])
        ->middleware('permission:accounts.change-state');


    // Account Features routes
    Route::prefix('{publicId}')->whereUuid('publicId')->group(function () {

        Route::get('/features', [AccountFeaturesController::class, 'index'])
            ->middleware('permission:accounts.features.view');

        Route::post('/features', [AccountFeaturesController::class, 'store'])
            ->middleware('permission:accounts.features.manage');

        Route::delete('/features/{featureKey}', [AccountFeaturesController::class, 'destroy'])
            ->middleware('permission:accounts.features.manage');

        // best demo endpoint for Decorator
        Route::get('/capabilities', [AccountFeaturesController::class, 'capabilities'])
            ->middleware('permission:accounts.features.view');
    });
});
