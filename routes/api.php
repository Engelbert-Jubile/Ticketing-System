<?php

use App\Http\Controllers\Main\DashboardController;

Route::get('/dashboard-stats', [DashboardController::class, 'stats'])
    ->name('api.dashboard.stats');
// kalau mau wajib login: ->middleware('auth:sanctum');
