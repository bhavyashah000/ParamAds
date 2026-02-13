<?php

use App\Modules\Campaigns\Controllers\CampaignController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization'])->prefix('campaigns')->group(function () {
    Route::get('/', [CampaignController::class, 'index']);
    Route::get('/{id}', [CampaignController::class, 'show']);
    Route::post('/{id}/pause', [CampaignController::class, 'pause'])->middleware('role:manager');
    Route::post('/{id}/activate', [CampaignController::class, 'activate'])->middleware('role:manager');
    Route::put('/{id}/budget', [CampaignController::class, 'updateBudget'])->middleware('role:manager');
    Route::post('/bulk-update', [CampaignController::class, 'bulkUpdate'])->middleware('role:manager');
});
