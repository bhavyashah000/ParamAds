<?php

use App\Modules\Automation\Controllers\AutomationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization'])->prefix('automation')->group(function () {
    Route::get('/rules', [AutomationController::class, 'index']);
    Route::post('/rules', [AutomationController::class, 'store'])->middleware('role:manager');
    Route::get('/rules/{id}', [AutomationController::class, 'show']);
    Route::put('/rules/{id}', [AutomationController::class, 'update'])->middleware('role:manager');
    Route::delete('/rules/{id}', [AutomationController::class, 'destroy'])->middleware('role:admin');
    Route::post('/rules/{id}/test', [AutomationController::class, 'testRun']);
    Route::get('/logs', [AutomationController::class, 'logs']);
});
