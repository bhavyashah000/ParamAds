<?php

use App\Modules\Retargeting\Controllers\RetargetingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization', 'subscription:audience_intelligence'])
    ->prefix('retargeting')
    ->group(function () {
        Route::get('/funnels', [RetargetingController::class, 'index']);
        Route::post('/funnels', [RetargetingController::class, 'store'])->middleware('role:manager');
        Route::get('/funnels/{id}', [RetargetingController::class, 'show']);
        Route::put('/funnels/{id}', [RetargetingController::class, 'update'])->middleware('role:manager');
        Route::delete('/funnels/{id}', [RetargetingController::class, 'destroy'])->middleware('role:admin');
        Route::get('/templates', [RetargetingController::class, 'templates']);
    });
