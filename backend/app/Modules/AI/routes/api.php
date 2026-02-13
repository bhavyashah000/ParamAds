<?php

use App\Modules\AI\Controllers\AIController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization'])
    ->prefix('ai')
    ->group(function () {
        Route::post('/forecast', [AIController::class, 'forecast']);
        Route::post('/anomalies', [AIController::class, 'anomalies']);
        Route::get('/insights', [AIController::class, 'insights']);
        Route::post('/ask', [AIController::class, 'ask']);
        Route::get('/health', [AIController::class, 'health']);
    });
