<?php

use Illuminate\Support\Facades\Route;
use App\Banking\Admin\Presentation\Http\Controllers\AdminController;

Route::middleware(['auth:sanctum', 'permission:admin.dashboard.view'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
    });
