<?php

use App\Modules\Agency\Controllers\AgencyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization'])
    ->prefix('agency')
    ->group(function () {
        // Sub-accounts (agency plan only)
        Route::middleware('subscription:agency')->group(function () {
            Route::get('/sub-accounts', [AgencyController::class, 'listSubAccounts']);
            Route::post('/sub-accounts', [AgencyController::class, 'createSubAccount'])->middleware('role:admin');
            Route::get('/sub-accounts/metrics', [AgencyController::class, 'subAccountMetrics']);
        });

        // White-label (enterprise plan only)
        Route::middleware('subscription:enterprise')->group(function () {
            Route::get('/white-label', [AgencyController::class, 'getWhiteLabel']);
            Route::put('/white-label', [AgencyController::class, 'updateWhiteLabel'])->middleware('role:admin');
        });

        // Team management
        Route::prefix('team')->group(function () {
            Route::get('/', [AgencyController::class, 'listTeam']);
            Route::post('/invite', [AgencyController::class, 'inviteMember'])->middleware('role:admin');
            Route::put('/{userId}/role', [AgencyController::class, 'updateMemberRole'])->middleware('role:admin');
            Route::delete('/{userId}', [AgencyController::class, 'removeMember'])->middleware('role:admin');
        });

        // Webhooks
        Route::prefix('webhooks')->middleware('role:admin')->group(function () {
            Route::get('/', [AgencyController::class, 'listWebhooks']);
            Route::post('/', [AgencyController::class, 'createWebhook']);
            Route::delete('/{id}', [AgencyController::class, 'deleteWebhook']);
            Route::get('/{id}/logs', [AgencyController::class, 'webhookLogs']);
        });
    });
