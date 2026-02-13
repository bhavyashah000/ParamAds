<?php

use App\Modules\CrossPlatform\Controllers\CrossPlatformController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization'])
    ->prefix('cross-platform')
    ->group(function () {
        Route::get('/unified-metrics', [CrossPlatformController::class, 'unifiedMetrics']);
        Route::get('/daily-metrics', [CrossPlatformController::class, 'dailyMetrics']);
        Route::get('/campaign-scores', [CrossPlatformController::class, 'campaignScores']);
        Route::get('/budget-recommendations', [CrossPlatformController::class, 'budgetRecommendations']);
    });
