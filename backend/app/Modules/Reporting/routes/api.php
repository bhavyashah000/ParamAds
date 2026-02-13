<?php

use App\Modules\Reporting\Controllers\ReportingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization'])
    ->prefix('reports')
    ->group(function () {
        Route::get('/', [ReportingController::class, 'index']);
        Route::post('/', [ReportingController::class, 'store'])->middleware('role:manager');
        Route::post('/generate', [ReportingController::class, 'generate']);
        Route::get('/{id}/history', [ReportingController::class, 'history']);
        Route::delete('/{id}', [ReportingController::class, 'destroy'])->middleware('role:admin');
    });
