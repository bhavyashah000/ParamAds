<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class AdminApiSettingsController extends Controller
{
    /**
     * Show API settings page.
     */
    public function index()
    {
        $settings = $this->getCurrentSettings();
        return view('admin.api-settings', compact('settings'));
    }

    /**
     * Update API settings.
     */
    public function update(Request $request)
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        $keys = [
            'STRIPE_KEY' => $request->stripe_key,
            'STRIPE_SECRET' => $request->stripe_secret,
            'STRIPE_WEBHOOK_SECRET' => $request->stripe_webhook_secret,
            'META_APP_ID' => $request->meta_app_id,
            'META_APP_SECRET' => $request->meta_app_secret,
            'GOOGLE_ADS_CLIENT_ID' => $request->google_ads_client_id,
            'GOOGLE_ADS_CLIENT_SECRET' => $request->google_ads_client_secret,
            'GOOGLE_ADS_DEVELOPER_TOKEN' => $request->google_ads_developer_token,
            'OPENAI_API_KEY' => $request->openai_api_key,
        ];

        foreach ($keys as $key => $value) {
            if ($value !== null) {
                $envContent = $this->setEnvValue($envContent, $key, $value);
            }
        }

        File::put($envPath, $envContent);
        Artisan::call('config:clear');

        return redirect()->route('admin.api-settings')->with('success', 'API settings updated successfully.');
    }

    /**
     * Show billing settings page.
     */
    public function billingSettings()
    {
        $settings = $this->getCurrentSettings();
        return view('admin.billing-settings', compact('settings'));
    }

    /**
     * Update billing settings.
     */
    public function updateBilling(Request $request)
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        $keys = [
            'STRIPE_KEY' => $request->stripe_key,
            'STRIPE_SECRET' => $request->stripe_secret,
            'STRIPE_WEBHOOK_SECRET' => $request->stripe_webhook_secret,
        ];

        foreach ($keys as $key => $value) {
            if ($value !== null) {
                $envContent = $this->setEnvValue($envContent, $key, $value);
            }
        }

        File::put($envPath, $envContent);
        Artisan::call('config:clear');

        return redirect()->route('admin.billing-settings')->with('success', 'Billing settings updated.');
    }

    /**
     * Show ad platforms settings page.
     */
    public function adPlatforms()
    {
        $settings = $this->getCurrentSettings();
        return view('admin.ad-platforms', compact('settings'));
    }

    /**
     * Update ad platform settings.
     */
    public function updateAdPlatforms(Request $request)
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        $keys = [
            'META_APP_ID' => $request->meta_app_id,
            'META_APP_SECRET' => $request->meta_app_secret,
            'META_REDIRECT_URI' => $request->meta_redirect_uri,
            'GOOGLE_ADS_CLIENT_ID' => $request->google_ads_client_id,
            'GOOGLE_ADS_CLIENT_SECRET' => $request->google_ads_client_secret,
            'GOOGLE_ADS_DEVELOPER_TOKEN' => $request->google_ads_developer_token,
            'GOOGLE_ADS_REDIRECT_URI' => $request->google_ads_redirect_uri,
        ];

        foreach ($keys as $key => $value) {
            if ($value !== null) {
                $envContent = $this->setEnvValue($envContent, $key, $value);
            }
        }

        File::put($envPath, $envContent);
        Artisan::call('config:clear');

        return redirect()->route('admin.ad-platforms')->with('success', 'Ad platform settings updated.');
    }

    /**
     * Show system settings page.
     */
    public function systemSettings()
    {
        $settings = $this->getCurrentSettings();
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'storage_used' => $this->getStorageUsage(),
        ];
        return view('admin.system', compact('settings', 'systemInfo'));
    }

    /**
     * Update system settings.
     */
    public function updateSystem(Request $request)
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        $keys = [
            'APP_NAME' => $request->app_name,
            'APP_URL' => $request->app_url,
            'APP_DEBUG' => $request->app_debug ? 'true' : 'false',
            'MAIL_MAILER' => $request->mail_mailer,
            'MAIL_HOST' => $request->mail_host,
            'MAIL_PORT' => $request->mail_port,
            'MAIL_USERNAME' => $request->mail_username,
            'MAIL_PASSWORD' => $request->mail_password,
            'MAIL_FROM_ADDRESS' => $request->mail_from_address,
            'AI_SERVICE_URL' => $request->ai_service_url,
        ];

        foreach ($keys as $key => $value) {
            if ($value !== null) {
                $envContent = $this->setEnvValue($envContent, $key, $value);
            }
        }

        File::put($envPath, $envContent);
        Artisan::call('config:clear');

        return redirect()->route('admin.system')->with('success', 'System settings updated.');
    }

    /**
     * Show logs page.
     */
    public function logs()
    {
        $logFile = storage_path('logs/laravel.log');
        $logs = '';
        if (File::exists($logFile)) {
            $logs = File::size($logFile) > 100000
                ? '... (truncated) ...' . "\n" . substr(File::get($logFile), -100000)
                : File::get($logFile);
        }
        return view('admin.logs', compact('logs'));
    }

    /**
     * Show organizations page.
     */
    public function organizations(Request $request)
    {
        $organizations = \App\Modules\Organizations\Models\Organization::withCount('users')
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.organizations', compact('organizations'));
    }

    // ---- Helpers ----

    private function getCurrentSettings(): array
    {
        return [
            'stripe_key' => $this->maskValue(env('STRIPE_KEY', '')),
            'stripe_secret' => $this->maskValue(env('STRIPE_SECRET', '')),
            'stripe_webhook_secret' => $this->maskValue(env('STRIPE_WEBHOOK_SECRET', '')),
            'meta_app_id' => env('META_APP_ID', ''),
            'meta_app_secret' => $this->maskValue(env('META_APP_SECRET', '')),
            'meta_redirect_uri' => env('META_REDIRECT_URI', ''),
            'google_ads_client_id' => env('GOOGLE_ADS_CLIENT_ID', ''),
            'google_ads_client_secret' => $this->maskValue(env('GOOGLE_ADS_CLIENT_SECRET', '')),
            'google_ads_developer_token' => $this->maskValue(env('GOOGLE_ADS_DEVELOPER_TOKEN', '')),
            'google_ads_redirect_uri' => env('GOOGLE_ADS_REDIRECT_URI', ''),
            'openai_api_key' => $this->maskValue(env('OPENAI_API_KEY', '')),
            'app_name' => env('APP_NAME', 'ParamAds'),
            'app_url' => env('APP_URL', ''),
            'app_debug' => env('APP_DEBUG', false),
            'mail_mailer' => env('MAIL_MAILER', 'smtp'),
            'mail_host' => env('MAIL_HOST', ''),
            'mail_port' => env('MAIL_PORT', '587'),
            'mail_username' => env('MAIL_USERNAME', ''),
            'mail_from_address' => env('MAIL_FROM_ADDRESS', ''),
            'ai_service_url' => env('AI_SERVICE_URL', 'http://127.0.0.1:8001'),
        ];
    }

    private function maskValue(string $value): string
    {
        if (empty($value)) return '';
        if (strlen($value) <= 8) return str_repeat('*', strlen($value));
        return substr($value, 0, 4) . str_repeat('*', strlen($value) - 8) . substr($value, -4);
    }

    private function setEnvValue(string $envContent, string $key, string $value): string
    {
        $escaped = str_contains($value, ' ') || str_contains($value, '#') ? "\"{$value}\"" : $value;
        if (preg_match("/^{$key}=.*/m", $envContent)) {
            return preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $envContent);
        }
        return $envContent . "\n{$key}={$escaped}";
    }

    private function getStorageUsage(): string
    {
        $bytes = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(storage_path())) as $file) {
            if ($file->isFile()) $bytes += $file->getSize();
        }
        return round($bytes / 1024 / 1024, 2) . ' MB';
    }
}
