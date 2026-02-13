<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class InstallerController extends Controller
{
    /**
     * Step 1: Welcome & System Requirements Check
     */
    public function welcome()
    {
        if ($this->isInstalled()) {
            return redirect('/login');
        }

        $requirements = $this->checkRequirements();
        return view('installer.welcome', compact('requirements'));
    }

    /**
     * Step 2: Environment / Database Configuration
     */
    public function environment()
    {
        if ($this->isInstalled()) return redirect('/login');
        return view('installer.environment');
    }

    /**
     * Save environment configuration
     */
    public function saveEnvironment(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        // Test database connection
        try {
            $pdo = new \PDO(
                "mysql:host={$request->db_host};port={$request->db_port};dbname={$request->db_database}",
                $request->db_username,
                $request->db_password
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            return back()->withErrors(['db_host' => 'Database connection failed: ' . $e->getMessage()])->withInput();
        }

        // Write .env file
        $envContent = $this->buildEnvContent($request->all());
        File::put(base_path('.env'), $envContent);

        // Clear config cache
        Artisan::call('config:clear');

        return redirect()->route('installer.api-keys');
    }

    /**
     * Step 3: API Keys Configuration
     */
    public function apiKeys()
    {
        if ($this->isInstalled()) return redirect('/login');
        return view('installer.api-keys');
    }

    /**
     * Save API keys
     */
    public function saveApiKeys(Request $request)
    {
        $request->validate([
            'stripe_key' => 'nullable|string',
            'stripe_secret' => 'nullable|string',
            'meta_app_id' => 'nullable|string',
            'meta_app_secret' => 'nullable|string',
            'google_ads_client_id' => 'nullable|string',
            'google_ads_client_secret' => 'nullable|string',
            'google_ads_developer_token' => 'nullable|string',
            'openai_api_key' => 'nullable|string',
            'mail_mailer' => 'nullable|string',
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|numeric',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_from_address' => 'nullable|email',
        ]);

        // Append API keys to .env
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        $apiKeys = [
            'STRIPE_KEY' => $request->stripe_key ?? '',
            'STRIPE_SECRET' => $request->stripe_secret ?? '',
            'STRIPE_WEBHOOK_SECRET' => '',
            'META_APP_ID' => $request->meta_app_id ?? '',
            'META_APP_SECRET' => $request->meta_app_secret ?? '',
            'GOOGLE_ADS_CLIENT_ID' => $request->google_ads_client_id ?? '',
            'GOOGLE_ADS_CLIENT_SECRET' => $request->google_ads_client_secret ?? '',
            'GOOGLE_ADS_DEVELOPER_TOKEN' => $request->google_ads_developer_token ?? '',
            'OPENAI_API_KEY' => $request->openai_api_key ?? '',
            'MAIL_MAILER' => $request->mail_mailer ?? 'smtp',
            'MAIL_HOST' => $request->mail_host ?? '',
            'MAIL_PORT' => $request->mail_port ?? '587',
            'MAIL_USERNAME' => $request->mail_username ?? '',
            'MAIL_PASSWORD' => $request->mail_password ?? '',
            'MAIL_FROM_ADDRESS' => $request->mail_from_address ?? '',
        ];

        foreach ($apiKeys as $key => $value) {
            $envContent = $this->setEnvValue($envContent, $key, $value);
        }

        File::put($envPath, $envContent);
        Artisan::call('config:clear');

        return redirect()->route('installer.admin');
    }

    /**
     * Step 4: Admin Account Setup
     */
    public function admin()
    {
        if ($this->isInstalled()) return redirect('/login');
        return view('installer.admin');
    }

    /**
     * Run installation and create admin
     */
    public function install(Request $request)
    {
        $request->validate([
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email',
            'admin_password' => 'required|string|min:8|confirmed',
        ]);

        try {
            // Generate app key
            Artisan::call('key:generate', ['--force' => true]);

            // Run migrations
            Artisan::call('migrate', ['--force' => true]);

            // Create storage link
            Artisan::call('storage:link');

            // Create admin organization
            $org = \App\Modules\Organizations\Models\Organization::create([
                'name' => 'Admin Organization',
                'slug' => 'admin-org',
                'plan' => 'enterprise',
                'settings' => ['is_admin' => true],
            ]);

            // Create admin user
            \App\Models\User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'role' => 'owner',
                'organization_id' => $org->id,
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);

            // Mark as installed
            File::put(storage_path('installed'), json_encode([
                'installed_at' => now()->toIso8601String(),
                'version' => '1.0.0',
            ]));

            // Cache config
            Artisan::call('config:cache');
            Artisan::call('route:cache');

            return redirect()->route('installer.complete');
        } catch (\Exception $e) {
            return back()->withErrors(['install' => 'Installation failed: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Step 5: Installation Complete
     */
    public function complete()
    {
        return view('installer.complete');
    }

    // ---- Helpers ----

    private function isInstalled(): bool
    {
        return File::exists(storage_path('installed'));
    }

    private function checkRequirements(): array
    {
        return [
            'PHP Version (>= 8.1)' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'BCMath Extension' => extension_loaded('bcmath'),
            'Ctype Extension' => extension_loaded('ctype'),
            'cURL Extension' => extension_loaded('curl'),
            'DOM Extension' => extension_loaded('dom'),
            'Fileinfo Extension' => extension_loaded('fileinfo'),
            'JSON Extension' => extension_loaded('json'),
            'Mbstring Extension' => extension_loaded('mbstring'),
            'OpenSSL Extension' => extension_loaded('openssl'),
            'PDO Extension' => extension_loaded('pdo'),
            'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
            'Tokenizer Extension' => extension_loaded('tokenizer'),
            'XML Extension' => extension_loaded('xml'),
            'GD Extension' => extension_loaded('gd'),
            'Storage Writable' => is_writable(storage_path()),
            'Cache Writable' => is_writable(base_path('bootstrap/cache')),
            '.env Writable' => is_writable(base_path('.env')) || is_writable(base_path()),
        ];
    }

    private function buildEnvContent(array $data): string
    {
        return "APP_NAME=\"{$data['app_name']}\"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL={$data['app_url']}

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST={$data['db_host']}
DB_PORT={$data['db_port']}
DB_DATABASE={$data['db_database']}
DB_USERNAME={$data['db_username']}
DB_PASSWORD={$data['db_password']}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

META_APP_ID=
META_APP_SECRET=
META_REDIRECT_URI={$data['app_url']}/api/ad-accounts/meta/callback

GOOGLE_ADS_CLIENT_ID=
GOOGLE_ADS_CLIENT_SECRET=
GOOGLE_ADS_DEVELOPER_TOKEN=
GOOGLE_ADS_REDIRECT_URI={$data['app_url']}/api/ad-accounts/google/callback

OPENAI_API_KEY=

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME=\"{$data['app_name']}\"

AI_SERVICE_URL=http://127.0.0.1:8001
";
    }

    private function setEnvValue(string $envContent, string $key, string $value): string
    {
        $escaped = str_contains($value, ' ') ? "\"{$value}\"" : $value;
        if (preg_match("/^{$key}=.*/m", $envContent)) {
            return preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $envContent);
        }
        return $envContent . "\n{$key}={$escaped}";
    }
}
