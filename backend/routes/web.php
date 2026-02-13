<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Installer\InstallerController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminApiSettingsController;
use App\Http\Controllers\Dashboard\UserDashboardController;
use App\Http\Controllers\Ads\AdCreationController;

/*
|--------------------------------------------------------------------------
| Web Installer Routes
|--------------------------------------------------------------------------
*/
Route::prefix('install')->group(function () {
    Route::get('/', [InstallerController::class, 'welcome'])->name('installer.welcome');
    Route::get('/environment', [InstallerController::class, 'environment'])->name('installer.environment');
    Route::post('/environment', [InstallerController::class, 'saveEnvironment'])->name('installer.save-environment');
    Route::get('/api-keys', [InstallerController::class, 'apiKeys'])->name('installer.api-keys');
    Route::post('/api-keys', [InstallerController::class, 'saveApiKeys'])->name('installer.save-api-keys');
    Route::get('/admin', [InstallerController::class, 'admin'])->name('installer.admin');
    Route::post('/install', [InstallerController::class, 'install'])->name('installer.install');
    Route::get('/complete', [InstallerController::class, 'complete'])->name('installer.complete');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login']);
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [WebAuthController::class, 'register']);
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Root Redirect
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (!\Illuminate\Support\Facades\File::exists(storage_path('installed'))) {
        return redirect('/install');
    }
    if (auth()->check()) {
        return auth()->user()->is_admin ? redirect('/admin') : redirect('/dashboard');
    }
    return redirect('/login');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users');
    Route::get('/users/{id}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    Route::get('/organizations', [AdminApiSettingsController::class, 'organizations'])->name('admin.organizations');
    Route::get('/api-settings', [AdminApiSettingsController::class, 'index'])->name('admin.api-settings');
    Route::put('/api-settings', [AdminApiSettingsController::class, 'update'])->name('admin.api-settings.update');
    Route::get('/billing-settings', [AdminApiSettingsController::class, 'billingSettings'])->name('admin.billing-settings');
    Route::put('/billing-settings', [AdminApiSettingsController::class, 'updateBilling'])->name('admin.billing-settings.update');
    Route::get('/ad-platforms', [AdminApiSettingsController::class, 'adPlatforms'])->name('admin.ad-platforms');
    Route::put('/ad-platforms', [AdminApiSettingsController::class, 'updateAdPlatforms'])->name('admin.ad-platforms.update');
    Route::get('/system', [AdminApiSettingsController::class, 'systemSettings'])->name('admin.system');
    Route::put('/system', [AdminApiSettingsController::class, 'updateSystem'])->name('admin.system.update');
    Route::get('/logs', [AdminApiSettingsController::class, 'logs'])->name('admin.logs');
    Route::post('/logs/clear', function () {
        \Illuminate\Support\Facades\File::put(storage_path('logs/laravel.log'), '');
        return back()->with('success', 'Logs cleared.');
    })->name('admin.logs.clear');
});

/*
|--------------------------------------------------------------------------
| User Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
    Route::get('/campaigns', [UserDashboardController::class, 'campaigns'])->name('campaigns.index');
    Route::get('/analytics', [UserDashboardController::class, 'analytics'])->name('analytics');
    Route::get('/audiences', [UserDashboardController::class, 'audiences'])->name('audiences');
    Route::get('/creatives', [UserDashboardController::class, 'creatives'])->name('creatives');
    Route::get('/automation', [UserDashboardController::class, 'automation'])->name('automation');
    Route::get('/ad-accounts', [UserDashboardController::class, 'adAccounts'])->name('ad-accounts');
    Route::get('/reports', [UserDashboardController::class, 'reports'])->name('reports');
    Route::get('/settings', [UserDashboardController::class, 'settings'])->name('settings');
    Route::put('/settings', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }
        $user->save();
        return back()->with('success', 'Profile updated.');
    })->name('settings.update');
    Route::get('/ads', [AdCreationController::class, 'index'])->name('ads.index');
    Route::get('/ads/create', [AdCreationController::class, 'create'])->name('ads.create');
    Route::post('/ads', [AdCreationController::class, 'store'])->name('ads.store');
});
