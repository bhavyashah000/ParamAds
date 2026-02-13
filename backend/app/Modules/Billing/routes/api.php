<?php

use App\Modules\Billing\Controllers\BillingController;
use App\Modules\Billing\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/plans', [BillingController::class, 'plans']);

// Stripe webhook (no auth, verified by signature)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

// Protected billing routes
Route::middleware(['auth:sanctum', 'organization'])->prefix('billing')->group(function () {
    Route::get('/subscription', [BillingController::class, 'subscription']);
    Route::post('/subscribe', [BillingController::class, 'subscribe'])
        ->middleware('role:owner');
    Route::put('/change-plan', [BillingController::class, 'changePlan'])
        ->middleware('role:owner');
    Route::post('/cancel', [BillingController::class, 'cancel'])
        ->middleware('role:owner');
    Route::post('/resume', [BillingController::class, 'resume'])
        ->middleware('role:owner');
    Route::get('/history', [BillingController::class, 'history']);
});
