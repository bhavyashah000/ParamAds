<?php

use App\Modules\Organizations\Controllers\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'organization'])->prefix('organization')->group(function () {
    Route::get('/', [OrganizationController::class, 'show']);
    Route::put('/', [OrganizationController::class, 'update']);
    Route::put('/settings', [OrganizationController::class, 'updateSettings']);
    Route::get('/usage', [OrganizationController::class, 'usageStats']);

    // Team management
    Route::get('/team', [OrganizationController::class, 'teamMembers']);
    Route::post('/team/invite', [OrganizationController::class, 'inviteMember'])
        ->middleware('role:admin');
    Route::put('/team/{userId}/role', [OrganizationController::class, 'updateMemberRole'])
        ->middleware('role:admin');
    Route::delete('/team/{userId}', [OrganizationController::class, 'removeMember'])
        ->middleware('role:admin');
});
