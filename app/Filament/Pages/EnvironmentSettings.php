<?php

namespace App\Filament\Pages;

use App\Models\DesignConfiguration;
use App\Services\DatabaseMigrationService;
use App\Services\EnvironmentService;
use App\Services\EnvWriter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class EnvironmentSettings extends Page
{
    protected static ?string $title = 'Environment Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 102;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    private const CATEGORY = 'environment_settings';

    public function mount(): void
    {
        $env = app(EnvironmentService::class);

        $this->form->fill([
            // Database settings (read from .env / config)
            'db_connection' => config('database.default'),
            'db_host' => config('database.connections.'.config('database.default').'.host') ?? '',
            'db_port' => (string) (config('database.connections.'.config('database.default').'.port') ?? ''),
            'db_database' => config('database.connections.'.config('database.default').'.database') ?? '',
            'db_username' => config('database.connections.'.config('database.default').'.username') ?? '',
            'db_password' => '',

            // Application (DB + .env)
            'app_name' => $env->appName(),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_url' => $env->appUrl(),
            'app_timezone' => $env->appTimezone(),
            'app_locale' => $env->appLocale(),
            'app_fallback_locale' => $env->appFallbackLocale(),
            'bcrypt_rounds' => (string) $env->bcryptRounds(),
            'filesystem_disk' => $env->filesystemDisk(),

            // Mail / SMTP
            'mail_mailer' => $env->mailMailer(),
            'mail_host' => $env->mailHost(),
            'mail_port' => (string) $env->mailPort(),
            'mail_username' => $env->mailUsername(),
            'mail_password' => '',
            'mail_from_address' => $env->mailFromAddress(),
            'mail_from_name' => $env->mailFromName(),
            'mail_scheme' => $env->mailScheme() ?? '',

            // Session
            'session_driver' => $env->sessionDriver(),
            'session_lifetime' => (string) $env->sessionLifetime(),
            'session_encrypt' => $env->sessionEncrypt(),
            'session_secure_cookie' => $env->sessionSecureCookie(),
            'session_domain' => $env->sessionDomain() ?? '',
            'session_same_site' => $env->sessionSameSite(),
            'session_http_only' => $env->sessionHttpOnly(),

            // Cache
            'cache_store' => $env->cacheStore(),
            'cache_prefix' => $env->cachePrefix(),

            // Queue
            'queue_connection' => $env->queueConnection(),

            // Redis
            'redis_host' => $env->redisHost(),
            'redis_port' => (string) $env->redisPort(),
            'redis_password' => '',

            // AWS / S3
            'aws_access_key_id' => '',
            'aws_secret_access_key' => '',
            'aws_default_region' => $env->awsDefaultRegion(),
            'aws_bucket' => $env->awsBucket(),

            // Logging
            'log_channel' => $env->logChannel(),
            'log_level' => $env->logLevel(),

            // Frontend / CORS
            'frontend_url' => $env->frontendUrl(),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Database')
                    ->description('Database connection settings. These are stored in the .env file since they are required for the application to boot. Changing these settings will update the .env file directly.')
                    ->icon('heroicon-o-circle-stack')
                    ->schema([
                        Select::make('db_connection')
                            ->label('Database Driver')
                            ->options([
                                'sqlite' => 'SQLite',
                                'mysql' => 'MySQL',
                                'mariadb' => 'MariaDB',
                                'pgsql' => 'PostgreSQL',
                                'sqlsrv' => 'SQL Server',
                            ])
                            ->required()
                            ->live()
                            ->helperText('The database engine to use. SQLite is file-based and requires no separate server.'),
                        TextInput::make('db_host')
                            ->label('Database Host')
                            ->placeholder('127.0.0.1')
                            ->visible(fn ($get): bool => $get('db_connection') !== 'sqlite')
                            ->helperText('The hostname or IP address of the database server.'),
                        TextInput::make('db_port')
                            ->label('Database Port')
                            ->numeric()
                            ->placeholder(fn ($get): string => match ($get('db_connection')) {
                                'mysql', 'mariadb' => '3306',
                                'pgsql' => '5432',
                                'sqlsrv' => '1433',
                                default => '',
                            })
                            ->visible(fn ($get): bool => $get('db_connection') !== 'sqlite')
                            ->helperText('Default ports: MySQL/MariaDB 3306, PostgreSQL 5432, SQL Server 1433.'),
                        TextInput::make('db_database')
                            ->label('Database Name')
                            ->required()
                            ->placeholder(fn ($get): string => $get('db_connection') === 'sqlite' ? 'database/database.sqlite' : 'your_database_name')
                            ->helperText(fn ($get): string => $get('db_connection') === 'sqlite'
                                ? 'Path to the SQLite file, relative to the project root.'
                                : 'The name of the database to connect to.'),
                        TextInput::make('db_username')
                            ->label('Database Username')
                            ->placeholder('root')
                            ->visible(fn ($get): bool => $get('db_connection') !== 'sqlite')
                            ->helperText('The username for database authentication.'),
                        TextInput::make('db_password')
                            ->label('Database Password')
                            ->password()
                            ->revealable()
                            ->placeholder('Leave blank to keep existing password')
                            ->visible(fn ($get): bool => $get('db_connection') !== 'sqlite')
                            ->helperText('The password for database authentication. Leave empty to keep unchanged.'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Application')
                    ->description('Core application settings that affect naming, URL generation, localization, and file storage.')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        TextInput::make('app_name')
                            ->label('Application Name')
                            ->required()
                            ->placeholder('Maimaar')
                            ->helperText('Displayed in the browser tab, emails, and notifications.'),
                        Select::make('app_env')
                            ->label('Environment')
                            ->options([
                                'local' => 'Local (Development)',
                                'staging' => 'Staging',
                                'production' => 'Production',
                                'testing' => 'Testing',
                            ])
                            ->required()
                            ->helperText('Set to "Production" on live servers. Affects error display and caching behavior.'),
                        Toggle::make('app_debug')
                            ->label('Debug Mode')
                            ->helperText('Shows detailed error pages. WARNING: Never enable on production servers — exposes sensitive data.'),
                        TextInput::make('app_url')
                            ->label('Application URL')
                            ->required()
                            ->url()
                            ->placeholder('https://your-domain.com')
                            ->helperText('The full URL where the application is accessible. Used for generating links and asset URLs.'),
                        Select::make('app_timezone')
                            ->label('Timezone')
                            ->options(collect(timezone_identifiers_list())->mapWithKeys(fn (string $tz) => [$tz => $tz])->toArray())
                            ->searchable()
                            ->required()
                            ->helperText('Default timezone for date/time operations.'),
                        Select::make('app_locale')
                            ->label('Locale')
                            ->options([
                                'en' => 'English',
                                'ar' => 'Arabic',
                                'fr' => 'French',
                                'de' => 'German',
                                'es' => 'Spanish',
                                'pt' => 'Portuguese',
                                'zh' => 'Chinese',
                                'ja' => 'Japanese',
                                'ko' => 'Korean',
                                'hi' => 'Hindi',
                                'ur' => 'Urdu',
                                'tr' => 'Turkish',
                                'ru' => 'Russian',
                            ])
                            ->required()
                            ->helperText('Primary language for the application.'),
                        Select::make('app_fallback_locale')
                            ->label('Fallback Locale')
                            ->options([
                                'en' => 'English',
                                'ar' => 'Arabic',
                                'fr' => 'French',
                                'de' => 'German',
                                'es' => 'Spanish',
                            ])
                            ->required()
                            ->helperText('Language used when a translation is missing in the primary locale.'),
                        TextInput::make('bcrypt_rounds')
                            ->label('Bcrypt Rounds')
                            ->numeric()
                            ->minValue(4)
                            ->maxValue(31)
                            ->placeholder('12')
                            ->helperText('Higher values increase password security but slow down login. 12 is recommended.'),
                        Select::make('filesystem_disk')
                            ->label('Default Filesystem Disk')
                            ->options([
                                'local' => 'Local',
                                'public' => 'Public',
                                's3' => 'Amazon S3',
                            ])
                            ->required()
                            ->live()
                            ->helperText('The default disk used for file storage operations.'),
                    ])
                    ->columns(2),

                Section::make('Mail / SMTP')
                    ->description('Configure how the application sends emails. Test your connection after making changes.')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        Select::make('mail_mailer')
                            ->label('Mail Driver')
                            ->options([
                                'smtp' => 'SMTP',
                                'log' => 'Log (development)',
                                'sendmail' => 'Sendmail',
                                'array' => 'Array (testing)',
                            ])
                            ->required()
                            ->helperText('SMTP is recommended for production. Log driver writes emails to the log file.'),
                        TextInput::make('mail_host')
                            ->label('SMTP Host')
                            ->placeholder('smtp.gmail.com')
                            ->helperText('The SMTP server hostname.'),
                        TextInput::make('mail_port')
                            ->label('SMTP Port')
                            ->numeric()
                            ->placeholder('587')
                            ->helperText('Common ports: 25 (unencrypted), 465 (SSL), 587 (TLS).'),
                        TextInput::make('mail_username')
                            ->label('SMTP Username')
                            ->placeholder('your-email@gmail.com')
                            ->helperText('Authentication username for the SMTP server.'),
                        TextInput::make('mail_password')
                            ->label('SMTP Password')
                            ->password()
                            ->revealable()
                            ->placeholder('Leave blank to keep existing password')
                            ->helperText('Authentication password. Leave empty to keep the current password unchanged.'),
                        Select::make('mail_scheme')
                            ->label('Encryption')
                            ->options([
                                '' => 'None',
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                            ])
                            ->helperText('TLS is recommended for most SMTP servers.'),
                        TextInput::make('mail_from_address')
                            ->label('From Address')
                            ->email()
                            ->required()
                            ->placeholder('noreply@your-domain.com')
                            ->helperText('The email address that appears as the sender.'),
                        TextInput::make('mail_from_name')
                            ->label('From Name')
                            ->required()
                            ->placeholder('Maimaar')
                            ->helperText('The name that appears as the sender.'),
                    ])
                    ->columns(2),

                Section::make('Session')
                    ->description('Control how user sessions are managed.')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Select::make('session_driver')
                            ->label('Session Driver')
                            ->options([
                                'database' => 'Database',
                                'file' => 'File',
                                'redis' => 'Redis',
                                'cookie' => 'Cookie',
                                'array' => 'Array (testing)',
                            ])
                            ->required()
                            ->live()
                            ->helperText('Database is recommended for most deployments. Changing the driver will log out all current users.'),
                        TextInput::make('session_lifetime')
                            ->label('Session Lifetime')
                            ->numeric()
                            ->required()
                            ->suffix('minutes')
                            ->placeholder('120')
                            ->helperText('How long (in minutes) a session stays active before expiring.'),
                        TextInput::make('session_domain')
                            ->label('Session Domain')
                            ->placeholder('.your-domain.com')
                            ->helperText('Leave empty for the current domain. Use a dot prefix (e.g. .example.com) to share sessions across subdomains.'),
                        Select::make('session_same_site')
                            ->label('Same-Site Cookie')
                            ->options([
                                'lax' => 'Lax (recommended)',
                                'strict' => 'Strict',
                                'none' => 'None',
                            ])
                            ->helperText('Controls cross-site cookie behavior. Lax is recommended for most applications.'),
                        Toggle::make('session_encrypt')
                            ->label('Encrypt Session Data')
                            ->helperText('When enabled, session data is encrypted before being stored.'),
                        Toggle::make('session_secure_cookie')
                            ->label('Secure Cookie (HTTPS only)')
                            ->helperText('When enabled, session cookies are only sent over HTTPS connections.'),
                        Toggle::make('session_http_only')
                            ->label('HTTP Only Cookie')
                            ->helperText('Prevents JavaScript from accessing the session cookie. Recommended for security.'),
                    ])
                    ->columns(2),

                Section::make('Logging')
                    ->description('Configure application logging behavior.')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Select::make('log_channel')
                            ->label('Log Channel')
                            ->options([
                                'stack' => 'Stack (multiple channels)',
                                'single' => 'Single file',
                                'daily' => 'Daily rotating files',
                                'errorlog' => 'PHP error_log',
                                'syslog' => 'System log',
                            ])
                            ->required()
                            ->helperText('Stack combines multiple channels. Daily creates a new log file each day with automatic cleanup.'),
                        Select::make('log_level')
                            ->label('Minimum Log Level')
                            ->options([
                                'debug' => 'Debug (all messages)',
                                'info' => 'Info',
                                'notice' => 'Notice',
                                'warning' => 'Warning',
                                'error' => 'Error (recommended for production)',
                                'critical' => 'Critical',
                                'alert' => 'Alert',
                                'emergency' => 'Emergency',
                            ])
                            ->required()
                            ->helperText('In production, "error" or "warning" is recommended to reduce log noise.'),
                    ])
                    ->columns(2),

                Section::make('Cache')
                    ->description('Configure application caching for improved performance.')
                    ->icon('heroicon-o-server-stack')
                    ->schema([
                        Select::make('cache_store')
                            ->label('Cache Store')
                            ->options([
                                'database' => 'Database',
                                'file' => 'File',
                                'redis' => 'Redis',
                                'memcached' => 'Memcached',
                                'array' => 'Array (no caching)',
                            ])
                            ->required()
                            ->live()
                            ->helperText('Database or file cache works without additional services. Redis requires a running Redis server.'),
                        TextInput::make('cache_prefix')
                            ->label('Cache Key Prefix')
                            ->placeholder('maimaar_cache_')
                            ->helperText('Prevents key collisions when sharing a cache backend with other applications.'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Queue')
                    ->description('Configure background job processing.')
                    ->icon('heroicon-o-queue-list')
                    ->schema([
                        Select::make('queue_connection')
                            ->label('Queue Driver')
                            ->options([
                                'sync' => 'Sync (immediate, no background)',
                                'database' => 'Database',
                                'redis' => 'Redis',
                            ])
                            ->required()
                            ->live()
                            ->helperText('Sync processes jobs immediately in the request. Database is good for most apps. Redis is fastest.'),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('Redis')
                    ->description('Configure Redis connection for cache, queue, and/or sessions.')
                    ->icon('heroicon-o-server')
                    ->visible(fn ($get): bool => in_array($get('cache_store'), ['redis'])
                        || in_array($get('queue_connection'), ['redis'])
                        || in_array($get('session_driver'), ['redis']))
                    ->schema([
                        TextInput::make('redis_host')
                            ->label('Redis Host')
                            ->placeholder('127.0.0.1')
                            ->helperText('The hostname or IP of the Redis server.'),
                        TextInput::make('redis_port')
                            ->label('Redis Port')
                            ->numeric()
                            ->placeholder('6379')
                            ->helperText('Default Redis port is 6379.'),
                        TextInput::make('redis_password')
                            ->label('Redis Password')
                            ->password()
                            ->revealable()
                            ->placeholder('Leave blank to keep existing')
                            ->helperText('Authentication password for the Redis server. Leave empty to keep unchanged.'),
                    ])
                    ->columns(3),

                Section::make('AWS / S3 Storage')
                    ->description('Configure Amazon S3 for file storage.')
                    ->icon('heroicon-o-cloud')
                    ->visible(fn ($get): bool => $get('filesystem_disk') === 's3')
                    ->schema([
                        TextInput::make('aws_access_key_id')
                            ->label('Access Key ID')
                            ->password()
                            ->revealable()
                            ->placeholder('Leave blank to keep existing')
                            ->helperText('Your AWS access key ID.'),
                        TextInput::make('aws_secret_access_key')
                            ->label('Secret Access Key')
                            ->password()
                            ->revealable()
                            ->placeholder('Leave blank to keep existing')
                            ->helperText('Your AWS secret access key.'),
                        Select::make('aws_default_region')
                            ->label('Region')
                            ->options([
                                'us-east-1' => 'US East (N. Virginia)',
                                'us-east-2' => 'US East (Ohio)',
                                'us-west-1' => 'US West (N. California)',
                                'us-west-2' => 'US West (Oregon)',
                                'eu-west-1' => 'EU (Ireland)',
                                'eu-west-2' => 'EU (London)',
                                'eu-central-1' => 'EU (Frankfurt)',
                                'ap-southeast-1' => 'Asia Pacific (Singapore)',
                                'ap-southeast-2' => 'Asia Pacific (Sydney)',
                                'ap-northeast-1' => 'Asia Pacific (Tokyo)',
                                'ap-south-1' => 'Asia Pacific (Mumbai)',
                                'me-south-1' => 'Middle East (Bahrain)',
                                'me-central-1' => 'Middle East (UAE)',
                                'af-south-1' => 'Africa (Cape Town)',
                                'sa-east-1' => 'South America (Sao Paulo)',
                            ])
                            ->searchable()
                            ->helperText('The AWS region where your S3 bucket is located.'),
                        TextInput::make('aws_bucket')
                            ->label('Bucket Name')
                            ->placeholder('my-bucket')
                            ->helperText('The S3 bucket name for file storage.'),
                    ])
                    ->columns(2),

                Section::make('Frontend / CORS')
                    ->description('Configure the frontend application URL for cross-origin resource sharing.')
                    ->icon('heroicon-o-link')
                    ->schema([
                        TextInput::make('frontend_url')
                            ->label('Frontend URL')
                            ->url()
                            ->placeholder('https://your-frontend.com')
                            ->helperText('The URL of the frontend SPA. Used for CORS headers to allow API requests. Leave empty if serving frontend from the same domain.'),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('System Information')
                    ->description('Read-only information about the server environment.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Placeholder::make('php_version')
                            ->label('PHP Version')
                            ->content(PHP_VERSION),
                        Placeholder::make('laravel_version')
                            ->label('Laravel Version')
                            ->content(app()->version()),
                        Placeholder::make('server_software')
                            ->label('Server Software')
                            ->content($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'),
                        Placeholder::make('loaded_extensions')
                            ->label('Loaded PHP Extensions')
                            ->content(implode(', ', get_loaded_extensions())),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment('start')
                            ->sticky($this->areFormActionsSticky())
                            ->key('form-actions'),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_database')
                ->label('Test DB Connection')
                ->icon('heroicon-o-circle-stack')
                ->color('info')
                ->action(function (): void {
                    try {
                        DB::connection()->getPdo();
                        $dbName = DB::connection()->getDatabaseName();

                        Notification::make()
                            ->success()
                            ->title('Database connection successful')
                            ->body("Connected to: {$dbName}")
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Database connection failed')
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    }
                }),
            Action::make('migrate_database')
                ->label('Migrate Data to New Database')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Migrate Database')
                ->modalDescription('This will export ALL data from the current database, set up the new database schema, and import all data. The target database must be accessible with the credentials currently saved in the form. This operation may take a while for large datasets.')
                ->action(fn () => $this->migrateDatabase()),
            Action::make('test_smtp')
                ->label('Test SMTP Connection')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('This will send a test email to your address using the currently saved SMTP settings.')
                ->action(function (): void {
                    try {
                        $env = app(EnvironmentService::class);

                        config([
                            'mail.default' => $env->mailMailer(),
                            'mail.mailers.smtp.host' => $env->mailHost(),
                            'mail.mailers.smtp.port' => $env->mailPort(),
                            'mail.mailers.smtp.username' => $env->mailUsername(),
                            'mail.mailers.smtp.password' => $env->mailPassword(),
                            'mail.mailers.smtp.scheme' => $env->mailScheme(),
                            'mail.from.address' => $env->mailFromAddress(),
                            'mail.from.name' => $env->mailFromName(),
                        ]);

                        $user = auth()->user();

                        Mail::to($user->email)->send(new \App\Mail\TestSmtpMail);

                        Notification::make()
                            ->success()
                            ->title('Test email sent')
                            ->body("A test email has been sent to {$user->email}.")
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('SMTP connection failed')
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Save database settings to .env (required for app to boot)
        $this->saveDatabaseSettings($data);

        // Save APP_ENV and APP_DEBUG to .env (needed at boot before DB)
        $envOnlySettings = [
            'APP_ENV' => $data['app_env'] ?? 'production',
            'APP_DEBUG' => ($data['app_debug'] ?? false) ? 'true' : 'false',
        ];
        app(EnvWriter::class)->setMany($envOnlySettings);

        // Save other settings to database
        $settings = [
            // Application
            'app_name' => $data['app_name'] ?? '',
            'app_url' => $data['app_url'] ?? '',
            'app_timezone' => $data['app_timezone'] ?? 'UTC',
            'app_locale' => $data['app_locale'] ?? 'en',
            'app_fallback_locale' => $data['app_fallback_locale'] ?? 'en',
            'bcrypt_rounds' => $data['bcrypt_rounds'] ?? '12',
            'filesystem_disk' => $data['filesystem_disk'] ?? 'local',

            // Mail
            'mail_mailer' => $data['mail_mailer'] ?? 'log',
            'mail_host' => $data['mail_host'] ?? '',
            'mail_port' => $data['mail_port'] ?? '2525',
            'mail_username' => $data['mail_username'] ?? '',
            'mail_from_address' => $data['mail_from_address'] ?? 'hello@example.com',
            'mail_from_name' => $data['mail_from_name'] ?? 'Maimaar',
            'mail_scheme' => $data['mail_scheme'] ?? '',

            // Session
            'session_driver' => $data['session_driver'] ?? 'database',
            'session_lifetime' => $data['session_lifetime'] ?? '120',
            'session_encrypt' => ($data['session_encrypt'] ?? false) ? 'true' : 'false',
            'session_secure_cookie' => ($data['session_secure_cookie'] ?? false) ? 'true' : 'false',
            'session_domain' => $data['session_domain'] ?? '',
            'session_same_site' => $data['session_same_site'] ?? 'lax',
            'session_http_only' => ($data['session_http_only'] ?? true) ? 'true' : 'false',

            // Cache
            'cache_store' => $data['cache_store'] ?? 'database',
            'cache_prefix' => $data['cache_prefix'] ?? '',

            // Queue
            'queue_connection' => $data['queue_connection'] ?? 'database',

            // Redis (non-sensitive)
            'redis_host' => $data['redis_host'] ?? '',
            'redis_port' => $data['redis_port'] ?? '',

            // AWS (non-sensitive)
            'aws_default_region' => $data['aws_default_region'] ?? '',
            'aws_bucket' => $data['aws_bucket'] ?? '',

            // Logging
            'log_channel' => $data['log_channel'] ?? 'stack',
            'log_level' => $data['log_level'] ?? 'debug',

            // Frontend
            'frontend_url' => $data['frontend_url'] ?? '',
        ];

        // Handle encrypted password/secret fields — only update if a new value was entered
        $sensitiveFields = [
            'mail_password',
            'redis_password',
            'aws_access_key_id',
            'aws_secret_access_key',
        ];

        foreach ($sensitiveFields as $field) {
            $newValue = $data[$field] ?? '';
            if ($newValue !== '') {
                $settings[$field] = Crypt::encryptString($newValue);
            }
        }

        foreach ($settings as $key => $value) {
            DesignConfiguration::query()->updateOrCreate(
                ['category' => self::CATEGORY, 'key' => $key],
                [
                    'value' => (string) $value,
                    'label' => str($key)->replace('_', ' ')->title()->toString(),
                ],
            );
        }

        app(EnvironmentService::class)->flushCache();

        Notification::make()
            ->success()
            ->title('Environment settings saved')
            ->body('Changes will take effect on the next request.')
            ->send();
    }

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    /**
     * Save database connection settings to the .env file.
     *
     * Database credentials must be stored in .env because the application
     * needs them to connect to the database during boot — before any
     * database-stored settings can be read.
     *
     * @param  array<string, mixed>  $data
     */
    private function saveDatabaseSettings(array $data): void
    {
        $connection = $data['db_connection'] ?? config('database.default');
        $envValues = ['DB_CONNECTION' => $connection];

        if ($connection === 'sqlite') {
            $envValues['DB_DATABASE'] = $this->resolveSqlitePath($data['db_database'] ?? '');
        } else {
            $envValues['DB_HOST'] = $data['db_host'] ?? '127.0.0.1';
            $envValues['DB_PORT'] = $data['db_port'] ?? '';
            $envValues['DB_DATABASE'] = $data['db_database'] ?? '';
            $envValues['DB_USERNAME'] = $data['db_username'] ?? '';

            $newDbPassword = $data['db_password'] ?? '';
            if ($newDbPassword !== '') {
                $envValues['DB_PASSWORD'] = $newDbPassword;
            }
        }

        app(EnvWriter::class)->setMany($envValues);
    }

    /**
     * Resolve a SQLite database path to an absolute path.
     *
     * Accepts:
     *  - Empty/blank → defaults to database/database.sqlite
     *  - Relative path (e.g. "database/database.sqlite") → resolved from base_path()
     *  - Absolute path → used as-is
     */
    private function resolveSqlitePath(string $path): string
    {
        $path = trim($path);

        if ($path === '' || $path === ':memory:') {
            return database_path('database.sqlite');
        }

        // Already an absolute path
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Relative path — resolve from project root
        return base_path($path);
    }

    /**
     * Export data from the current database, update the .env to point to a
     * new database engine, run migrations on it, then import the data.
     */
    public function migrateDatabase(): void
    {
        $formData = $this->form->getState();
        $newConnection = $formData['db_connection'] ?? config('database.default');
        $currentConnection = config('database.default');

        if ($newConnection === $currentConnection) {
            Notification::make()
                ->warning()
                ->title('Same database driver')
                ->body('The selected database driver is the same as the current one. No migration is needed.')
                ->send();

            return;
        }

        // Snapshot current .env DB settings so we can restore on failure.
        $previousEnv = [
            'DB_CONNECTION' => config('database.default'),
            'DB_HOST' => config('database.connections.'.$currentConnection.'.host') ?? '127.0.0.1',
            'DB_PORT' => (string) (config('database.connections.'.$currentConnection.'.port') ?? ''),
            'DB_DATABASE' => config('database.connections.'.$currentConnection.'.database') ?? '',
            'DB_USERNAME' => config('database.connections.'.$currentConnection.'.username') ?? '',
        ];

        try {
            $service = app(DatabaseMigrationService::class);

            // Step 1: Export all data from current database
            $data = $service->exportData();

            $totalRows = array_sum(array_map('count', $data));

            // Step 2: For SQLite targets, ensure the database file exists
            if ($newConnection === 'sqlite') {
                $sqlitePath = $this->resolveSqlitePath($formData['db_database'] ?? '');

                if (! file_exists($sqlitePath)) {
                    $dir = dirname($sqlitePath);

                    if (! is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }

                    touch($sqlitePath);
                }
            }

            // Step 3: Update .env with new database credentials
            $this->saveDatabaseSettings($formData);

            // Step 4: Run migrations on the new database in a subprocess
            // so the new .env values are picked up by a fresh app instance.
            $migrateResult = Process::path(base_path())
                ->timeout(120)
                ->run(['php', 'artisan', 'migrate', '--force', '--no-interaction']);

            if (! $migrateResult->successful()) {
                throw new \RuntimeException(
                    'Migration failed: '.trim($migrateResult->errorOutput())
                );
            }

            // Step 5: Configure the new connection dynamically for import.
            // We set up a temporary connection using the form data so the
            // import runs against the new database within this request.
            $this->configureDynamicConnection($newConnection, $formData);

            // Step 6: Import data into new database
            $service->importData($data, $newConnection);

            // Step 7: Verify row counts
            $verification = $service->verifyMigration($data, $newConnection);
            $mismatches = array_filter($verification, fn ($v) => ! $v['match']);

            if (! empty($mismatches)) {
                $details = collect($mismatches)
                    ->map(fn ($v, $table) => "{$table}: expected {$v['source']}, got {$v['target']}")
                    ->implode('; ');

                Notification::make()
                    ->warning()
                    ->title('Migration completed with warnings')
                    ->body("Transferred {$totalRows} rows. Row count mismatches: {$details}")
                    ->persistent()
                    ->send();
            } else {
                Notification::make()
                    ->success()
                    ->title('Database migration completed')
                    ->body("Successfully transferred {$totalRows} rows to the new {$newConnection} database.")
                    ->send();
            }
        } catch (\Throwable $e) {
            // Restore previous .env settings so the app continues working
            app(EnvWriter::class)->setMany($previousEnv);

            Notification::make()
                ->danger()
                ->title('Database migration failed')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    /**
     * Set up a dynamic database connection using the form data
     * so we can import data within the current request.
     *
     * @param  array<string, mixed>  $formData
     */
    private function configureDynamicConnection(string $driver, array $formData): void
    {
        $baseConfig = config("database.connections.{$driver}", []);

        $overrides = match ($driver) {
            'sqlite' => [
                'database' => $this->resolveSqlitePath($formData['db_database'] ?? ''),
            ],
            default => [
                'host' => $formData['db_host'] ?? '127.0.0.1',
                'port' => $formData['db_port'] ?? '',
                'database' => $formData['db_database'] ?? '',
                'username' => $formData['db_username'] ?? '',
                'password' => $formData['db_password'] ?? ($baseConfig['password'] ?? ''),
            ],
        };

        config(["database.connections.{$driver}" => array_merge($baseConfig, $overrides)]);

        DB::purge($driver);
    }
}
