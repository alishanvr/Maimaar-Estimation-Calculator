<?php

namespace App\Http\Controllers;

use App\Models\DesignConfiguration;
use App\Models\User;
use App\Services\EnvWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class InstallerController extends Controller
{
    private const STEPS = [
        'welcome',
        'requirements',
        'database',
        'migrations',
        'application',
        'mail',
        'admin',
        'finalize',
    ];

    // ─── Step 0: Welcome ────────────────────────────────

    public function showWelcome(): View
    {
        return view('installer.welcome', ['step' => 0, 'steps' => self::STEPS]);
    }

    // ─── Step 1: Server Requirements ────────────────────

    public function showRequirements(): View
    {
        $requirements = $this->checkRequirements();

        return view('installer.requirements', [
            'step' => 1,
            'steps' => self::STEPS,
            'requirements' => $requirements,
            'allPassed' => collect($requirements)->every(fn (array $item): bool => $item['passed']),
        ]);
    }

    // ─── Step 2: Database ───────────────────────────────

    public function showDatabase(): View
    {
        return view('installer.database', [
            'step' => 2,
            'steps' => self::STEPS,
            'old' => [
                'db_connection' => env('DB_CONNECTION', 'mysql'),
                'db_host' => env('DB_HOST', '127.0.0.1'),
                'db_port' => env('DB_PORT', '3306'),
                'db_database' => env('DB_DATABASE', ''),
                'db_username' => env('DB_USERNAME', 'root'),
            ],
        ]);
    }

    public function saveDatabase(Request $request, EnvWriter $envWriter): RedirectResponse
    {
        $validated = $request->validate([
            'db_connection' => 'required|in:mysql,sqlite,pgsql',
            'db_host' => 'required_unless:db_connection,sqlite|string|max:255',
            'db_port' => 'required_unless:db_connection,sqlite|numeric',
            'db_database' => 'required|string|max:255',
            'db_username' => 'required_unless:db_connection,sqlite|string|max:255',
            'db_password' => 'nullable|string|max:255',
        ]);

        // Test database connection before writing
        try {
            $this->testDatabaseConnection($validated);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['db_connection' => 'Database connection failed: '.$e->getMessage()]);
        }

        $envWriter->setMany([
            'DB_CONNECTION' => $validated['db_connection'],
            'DB_HOST' => $validated['db_host'] ?? '127.0.0.1',
            'DB_PORT' => $validated['db_port'] ?? '3306',
            'DB_DATABASE' => $validated['db_database'],
            'DB_USERNAME' => $validated['db_username'] ?? 'root',
            'DB_PASSWORD' => $validated['db_password'] ?? '',
        ]);

        return redirect()->route('install.migrations');
    }

    // ─── Step 3: Migrations ─────────────────────────────

    public function showMigrations(): View
    {
        return view('installer.migrations', ['step' => 3, 'steps' => self::STEPS]);
    }

    public function runMigrations(Request $request): RedirectResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
            $migrationOutput = Artisan::output();

            $seedClasses = [
                \Database\Seeders\ReferenceDataSeeder::class,
                \Database\Seeders\PdfSettingsSeeder::class,
                \Database\Seeders\AppSettingsSeeder::class,
                \Database\Seeders\EnvironmentSettingsSeeder::class,
                \Database\Seeders\SuperAdminSeeder::class,
            ];

            if ($request->boolean('seed_reference_data', true)) {
                foreach ($seedClasses as $seederClass) {
                    Artisan::call('db:seed', [
                        '--class' => $seederClass,
                        '--force' => true,
                        '--no-interaction' => true,
                    ]);
                }
            }

            return redirect()->route('install.application')
                ->with('migration_output', $migrationOutput);
        } catch (\Throwable $e) {
            return back()->withErrors([
                'migration' => 'Migration failed: '.$e->getMessage(),
            ]);
        }
    }

    // ─── Step 4: Application Settings ───────────────────

    public function showApplication(): View
    {
        return view('installer.application', [
            'step' => 4,
            'steps' => self::STEPS,
            'old' => [
                'app_name' => env('APP_NAME', 'Maimaar Estimation Calculator'),
                'app_url' => env('APP_URL', 'http://localhost'),
                'app_env' => env('APP_ENV', 'production'),
            ],
        ]);
    }

    public function saveApplication(Request $request, EnvWriter $envWriter): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url|max:255',
            'app_env' => 'required|in:production,local,staging',
        ]);

        $envWriter->setMany([
            'APP_NAME' => $validated['app_name'],
            'APP_URL' => $validated['app_url'],
            'APP_ENV' => $validated['app_env'],
            'APP_DEBUG' => $validated['app_env'] === 'production' ? 'false' : 'true',
        ]);

        // Also store in design_configurations for the AppSettingsService
        DesignConfiguration::query()->updateOrCreate(
            ['category' => 'app_settings', 'key' => 'app_name'],
            ['value' => $validated['app_name'], 'label' => 'App Name'],
        );

        // Store app_url in environment_settings
        DesignConfiguration::query()->updateOrCreate(
            ['category' => 'environment_settings', 'key' => 'app_url'],
            ['value' => $validated['app_url'], 'label' => 'App URL'],
        );

        return redirect()->route('install.mail');
    }

    // ─── Step 5: Mail Configuration ─────────────────────

    public function showMail(): View
    {
        return view('installer.mail', ['step' => 5, 'steps' => self::STEPS]);
    }

    public function saveMail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail_mailer' => 'required|in:smtp,log,sendmail,array',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|numeric',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'required|string|max:255',
            'mail_scheme' => 'nullable|in:tls,ssl',
        ]);

        $settings = [
            'mail_mailer' => $validated['mail_mailer'],
            'mail_host' => $validated['mail_host'] ?? '',
            'mail_port' => $validated['mail_port'] ?? '2525',
            'mail_username' => $validated['mail_username'] ?? '',
            'mail_from_address' => $validated['mail_from_address'],
            'mail_from_name' => $validated['mail_from_name'],
            'mail_scheme' => $validated['mail_scheme'] ?? '',
        ];

        // Encrypt mail password
        if (! empty($validated['mail_password'])) {
            $settings['mail_password'] = Crypt::encryptString($validated['mail_password']);
        } else {
            $settings['mail_password'] = '';
        }

        foreach ($settings as $key => $value) {
            DesignConfiguration::query()->updateOrCreate(
                ['category' => 'environment_settings', 'key' => $key],
                [
                    'value' => (string) $value,
                    'label' => str($key)->replace('_', ' ')->title()->toString(),
                ],
            );
        }

        return redirect()->route('install.admin');
    }

    // ─── Step 6: Admin Account ──────────────────────────

    public function showAdmin(): View
    {
        return view('installer.admin', ['step' => 6, 'steps' => self::STEPS]);
    }

    public function saveAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'nullable|string|max:255',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'status' => 'active',
            'company_name' => $validated['company_name'] ?? '',
        ]);

        return redirect()->route('install.finalize');
    }

    // ─── Step 7: Finalize ───────────────────────────────

    public function showFinalize(): View
    {
        return view('installer.finalize', ['step' => 7, 'steps' => self::STEPS]);
    }

    public function runFinalize(EnvWriter $envWriter): RedirectResponse
    {
        // Ensure storage symlink exists
        try {
            Artisan::call('storage:link', ['--no-interaction' => true]);
        } catch (\Throwable) {
            // May already exist — ignore
        }

        // Generate APP_KEY if not set
        if (empty(env('APP_KEY'))) {
            Artisan::call('key:generate', ['--force' => true, '--no-interaction' => true]);
        }

        // Ensure frontend URL is set
        $frontendUrl = env('FRONTEND_URL');
        if (empty($frontendUrl)) {
            $envWriter->set('FRONTEND_URL', config('app.url', 'http://localhost').':3000');
        }

        // Write the installed flag
        $installedPath = storage_path('app/installed');
        $directory = dirname($installedPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        file_put_contents($installedPath, now()->toIso8601String());

        // Clear caches for fresh start
        Artisan::call('config:clear', ['--no-interaction' => true]);
        Artisan::call('cache:clear', ['--no-interaction' => true]);

        return redirect('/admin')->with('success', 'Installation completed successfully! Please log in with your admin credentials.');
    }

    // ─── Helpers ────────────────────────────────────────

    /**
     * Check server requirements.
     *
     * @return array<int, array{name: string, passed: bool, note: string}>
     */
    private function checkRequirements(): array
    {
        $requirements = [];

        // PHP Version
        $requirements[] = [
            'name' => 'PHP >= 8.2',
            'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'note' => 'Current: '.PHP_VERSION,
        ];

        // Required extensions
        $extensions = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo'];
        foreach ($extensions as $ext) {
            $requirements[] = [
                'name' => "PHP Extension: {$ext}",
                'passed' => extension_loaded($ext),
                'note' => extension_loaded($ext) ? 'Installed' : 'Missing',
            ];
        }

        // GD or Imagick
        $hasGd = extension_loaded('gd');
        $hasImagick = extension_loaded('imagick');
        $requirements[] = [
            'name' => 'PHP Extension: gd or imagick',
            'passed' => $hasGd || $hasImagick,
            'note' => $hasGd ? 'GD installed' : ($hasImagick ? 'Imagick installed' : 'Missing'),
        ];

        // Directory permissions
        $directories = [
            'storage/' => storage_path(),
            'storage/app/' => storage_path('app'),
            'storage/logs/' => storage_path('logs'),
            'bootstrap/cache/' => base_path('bootstrap/cache'),
        ];

        foreach ($directories as $label => $path) {
            $requirements[] = [
                'name' => "Writable: {$label}",
                'passed' => is_writable($path),
                'note' => is_writable($path) ? 'Writable' : 'Not writable',
            ];
        }

        // .env file
        $envExists = file_exists(base_path('.env'));
        $requirements[] = [
            'name' => '.env file exists',
            'passed' => $envExists,
            'note' => $envExists ? 'Found' : 'Will be created from .env.example',
        ];

        return $requirements;
    }

    /**
     * Test database connection with provided credentials.
     *
     * @param  array<string, mixed>  $config
     */
    private function testDatabaseConnection(array $config): void
    {
        $driver = $config['db_connection'];

        if ($driver === 'sqlite') {
            $dbPath = $config['db_database'];
            if (! file_exists($dbPath)) {
                touch($dbPath);
            }
            new \PDO("sqlite:{$dbPath}");

            return;
        }

        $host = $config['db_host'];
        $port = $config['db_port'];
        $database = $config['db_database'];
        $username = $config['db_username'];
        $password = $config['db_password'] ?? '';

        $dsn = match ($driver) {
            'pgsql' => "pgsql:host={$host};port={$port};dbname={$database}",
            default => "mysql:host={$host};port={$port};dbname={$database}",
        };

        new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_TIMEOUT => 5,
        ]);
    }
}
