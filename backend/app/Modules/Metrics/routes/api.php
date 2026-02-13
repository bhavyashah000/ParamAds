<?php

use App\Modules\Metrics\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization'])->prefix('dashboard')->group(function () {
    Route::get('/kpi', [DashboardController::class, 'kpiSummary']);
    Route::get('/campaigns', [DashboardController::class, 'campaignPerformance']);
    Route::get('/comparison', [DashboardController::class, 'timeComparison']);
});
