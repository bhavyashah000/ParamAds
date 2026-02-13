<?php

use App\Modules\AdAccounts\Controllers\AdAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization'])->prefix('ad-accounts')->group(function () {
    Route::get('/', [AdAccountController::class, 'index']);
    Route::get('/{id}', [AdAccountController::class, 'show']);
    Route::post('/connect', [AdAccountController::class, 'connect']);
    Route::post('/meta/callback', [AdAccountController::class, 'metaCallback']);
    Route::post('/google/callback', [AdAccountController::class, 'googleCallback']);
    Route::post('/{id}/disconnect', [AdAccountController::class, 'disconnect']);
    Route::post('/{id}/sync', [AdAccountController::class, 'syncCampaigns']);
});
