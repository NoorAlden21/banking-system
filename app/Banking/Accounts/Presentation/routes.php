<?php

use Illuminate\Support\Facades\Route;
use App\Banking\Accounts\Presentation\Http\Controllers\AccountsController;

Route::middleware(['auth:sanctum'])->prefix('accounts')->group(function () {
    Route::get('/', [AccountsController::class, 'index']);
});
