<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| ParamAds API Routes - All module routes are loaded here.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => config('paramads.version', '1.0.0'),
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Module routes - loaded conditionally to handle missing files gracefully
$moduleRoutes = [
    'Modules/Auth/routes/api.php',
    'Modules/Organizations/routes/api.php',
    'Modules/Billing/routes/api.php',
    'Modules/AdAccounts/routes/api.php',
    'Modules/Campaigns/routes/api.php',
    'Modules/Metrics/routes/api.php',
    'Modules/Automation/routes/api.php',
    'Modules/CreativeIntelligence/routes/api.php',
    'Modules/AudienceIntelligence/routes/api.php',
    'Modules/Retargeting/routes/api.php',
    'Modules/AI/routes/api.php',
    'Modules/CrossPlatform/routes/api.php',
    'Modules/Agency/routes/api.php',
    'Modules/Reporting/routes/api.php',
];

foreach ($moduleRoutes as $routeFile) {
    $path = app_path($routeFile);
    if (file_exists($path)) {
        require $path;
    }
}
