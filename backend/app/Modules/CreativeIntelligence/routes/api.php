<?php

use App\Modules\CreativeIntelligence\Controllers\CreativeIntelligenceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization', 'subscription:creative_intelligence'])
    ->prefix('creative-intelligence')
    ->group(function () {
        Route::get('/creatives', [CreativeIntelligenceController::class, 'index']);
        Route::get('/creatives/{id}', [CreativeIntelligenceController::class, 'show']);
        Route::get('/top-performers', [CreativeIntelligenceController::class, 'topPerformers']);
        Route::get('/fatigued', [CreativeIntelligenceController::class, 'fatigued']);
        Route::post('/analyze', [CreativeIntelligenceController::class, 'analyze']);
        Route::post('/compare', [CreativeIntelligenceController::class, 'compare']);
        Route::get('/trends', [CreativeIntelligenceController::class, 'trends']);
    });
