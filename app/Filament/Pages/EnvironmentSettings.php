<?php

namespace App\Filament\Pages;

use App\Models\DesignConfiguration;
use App\Services\EnvironmentService;
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
use Illuminate\Support\Facades\Mail;
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
            'app_url' => $env->appUrl(),
            'filesystem_disk' => $env->filesystemDisk(),
            'mail_mailer' => $env->mailMailer(),
            'mail_host' => $env->mailHost(),
            'mail_port' => (string) $env->mailPort(),
            'mail_username' => $env->mailUsername(),
            'mail_password' => '',
            'mail_from_address' => $env->mailFromAddress(),
            'mail_from_name' => $env->mailFromName(),
            'mail_scheme' => $env->mailScheme() ?? '',
            'session_lifetime' => (string) $env->sessionLifetime(),
            'session_encrypt' => $env->sessionEncrypt(),
            'session_secure_cookie' => $env->sessionSecureCookie(),
            'log_channel' => $env->logChannel(),
            'log_level' => $env->logLevel(),
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
                Section::make('Application')
                    ->description('Core application settings that affect URL generation and file storage.')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        TextInput::make('app_url')
                            ->label('Application URL')
                            ->required()
                            ->url()
                            ->placeholder('https://your-domain.com')
                            ->helperText('The full URL where the application is accessible. Used for generating links and asset URLs.'),
                        Select::make('filesystem_disk')
                            ->label('Default Filesystem Disk')
                            ->options([
                                'local' => 'Local',
                                'public' => 'Public',
                                's3' => 'Amazon S3',
                            ])
                            ->required()
                            ->helperText('The default disk used for file storage operations.'),
                    ])
                    ->columns(1),

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
                        TextInput::make('session_lifetime')
                            ->label('Session Lifetime')
                            ->numeric()
                            ->required()
                            ->suffix('minutes')
                            ->placeholder('120')
                            ->helperText('How long (in minutes) a session stays active before expiring.'),
                        Toggle::make('session_encrypt')
                            ->label('Encrypt Session Data')
                            ->helperText('When enabled, session data is encrypted before being stored. Adds security but slightly increases overhead.'),
                        Toggle::make('session_secure_cookie')
                            ->label('Secure Cookie (HTTPS only)')
                            ->helperText('When enabled, session cookies are only sent over HTTPS connections. Enable this in production with SSL.'),
                    ])
                    ->columns(1),

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

                Section::make('Frontend / CORS')
                    ->description('Configure the frontend application URL for cross-origin resource sharing.')
                    ->icon('heroicon-o-link')
                    ->schema([
                        TextInput::make('frontend_url')
                            ->label('Frontend URL')
                            ->url()
                            ->placeholder('https://your-frontend.com')
                            ->helperText('The URL of the frontend SPA. Used for CORS headers to allow API requests.'),
                    ])
                    ->columns(1),

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

        $settings = [
            'app_url' => $data['app_url'] ?? '',
            'filesystem_disk' => $data['filesystem_disk'] ?? 'local',
            'mail_mailer' => $data['mail_mailer'] ?? 'log',
            'mail_host' => $data['mail_host'] ?? '',
            'mail_port' => $data['mail_port'] ?? '2525',
            'mail_username' => $data['mail_username'] ?? '',
            'mail_from_address' => $data['mail_from_address'] ?? 'hello@example.com',
            'mail_from_name' => $data['mail_from_name'] ?? 'Maimaar',
            'mail_scheme' => $data['mail_scheme'] ?? '',
            'session_lifetime' => $data['session_lifetime'] ?? '120',
            'session_encrypt' => ($data['session_encrypt'] ?? false) ? 'true' : 'false',
            'session_secure_cookie' => ($data['session_secure_cookie'] ?? false) ? 'true' : 'false',
            'log_channel' => $data['log_channel'] ?? 'stack',
            'log_level' => $data['log_level'] ?? 'debug',
            'frontend_url' => $data['frontend_url'] ?? '',
        ];

        // Handle password: only update if a new value was entered
        $newPassword = $data['mail_password'] ?? '';
        if ($newPassword !== '') {
            $settings['mail_password'] = Crypt::encryptString($newPassword);
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
}
