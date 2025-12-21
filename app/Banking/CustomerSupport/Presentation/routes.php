<?php

use Illuminate\Support\Facades\Route;
use App\Banking\CustomerSupport\Presentation\Http\Controllers\SupportTicketsController;

Route::middleware(['auth:sanctum'])->prefix('support/tickets')->group(function () {

    Route::get('/', [SupportTicketsController::class, 'index'])
        ->middleware('permission:support.tickets.view');

    Route::post('/', [SupportTicketsController::class, 'store'])
        ->middleware('permission:support.tickets.create');

    Route::get('/{publicId}', [SupportTicketsController::class, 'show'])
        ->whereUuid('publicId')
        ->middleware('permission:support.tickets.view');

    Route::post('/{publicId}/messages', [SupportTicketsController::class, 'addMessage'])
        ->whereUuid('publicId')
        ->middleware('permission:support.tickets.reply');

    Route::patch('/{publicId}/status', [SupportTicketsController::class, 'changeStatus'])
        ->whereUuid('publicId')
        ->middleware('permission:support.tickets.change-status');

    Route::patch('/{publicId}/assign', [SupportTicketsController::class, 'assign'])
        ->whereUuid('publicId')
        ->middleware('permission:support.tickets.assign');

    Route::delete('/{publicId}', [SupportTicketsController::class, 'destroy'])
        ->whereUuid('publicId')
        ->middleware('permission:support.tickets.delete');
});
