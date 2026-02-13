<?php

use App\Modules\AudienceIntelligence\Controllers\AudienceIntelligenceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization', 'subscription:audience_intelligence'])
    ->prefix('audience-intelligence')
    ->group(function () {
        Route::get('/audiences', [AudienceIntelligenceController::class, 'index']);
        Route::get('/insights', [AudienceIntelligenceController::class, 'insights']);
        Route::post('/audiences/{adAccountId}/sync', [AudienceIntelligenceController::class, 'sync']);
        Route::post('/overlap', [AudienceIntelligenceController::class, 'overlap']);
        Route::get('/pixels', [AudienceIntelligenceController::class, 'pixels']);
        Route::post('/pixels/{adAccountId}/sync', [AudienceIntelligenceController::class, 'syncPixels']);
    });
