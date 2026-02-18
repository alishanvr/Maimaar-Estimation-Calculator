<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use UnitEnum;

class SystemManagement extends Page
{
    protected static ?string $title = 'System Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 103;

    /**
     * Whether the current user can perform system management actions (superadmin only).
     */
    private function canModify(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function content(Schema $schema): Schema
    {
        $readOnly = ! $this->canModify();

        return $schema
            ->components([
                Section::make('Cache Management')
                    ->description($readOnly
                        ? 'You can view system settings but only the super admin can perform these actions.'
                        : 'Clear various application caches. Use this after making configuration changes or when the application behaves unexpectedly.')
                    ->icon('heroicon-o-trash')
                    ->footerActions($readOnly ? [] : [
                        Action::make('clear_application_cache')
                            ->label('Clear Application Cache')
                            ->icon('heroicon-o-archive-box-x-mark')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalDescription('This will flush the application cache (cached queries, settings, etc.).')
                            ->action(fn () => $this->clearApplicationCache()),
                        Action::make('clear_config_cache')
                            ->label('Clear Config Cache')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalDescription('This will clear the configuration cache. The config will be re-read from files on the next request.')
                            ->action(fn () => $this->clearConfigCache()),
                        Action::make('clear_route_cache')
                            ->label('Clear Route Cache')
                            ->icon('heroicon-o-map')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalDescription('This will clear the route cache. Routes will be re-compiled on the next request.')
                            ->action(fn () => $this->clearRouteCache()),
                        Action::make('clear_view_cache')
                            ->label('Clear View Cache')
                            ->icon('heroicon-o-eye')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalDescription('This will clear compiled Blade view files. Views will be re-compiled on the next request.')
                            ->action(fn () => $this->clearViewCache()),
                        Action::make('clear_all_caches')
                            ->label('Clear All Caches')
                            ->icon('heroicon-o-fire')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalDescription('This will clear ALL caches: application, config, routes, views, and events. Use this for a complete reset.')
                            ->action(fn () => $this->clearAllCaches()),
                    ]),

                Section::make('Optimize Application')
                    ->description($readOnly
                        ? 'Application optimization is managed by the super admin.'
                        : 'Cache configuration, routes, and views for better performance in production. No need to clear caches first — this rebuilds them automatically.')
                    ->icon('heroicon-o-bolt')
                    ->footerActions($readOnly ? [] : [
                        Action::make('optimize')
                            ->label('Optimize Application')
                            ->icon('heroicon-o-rocket-launch')
                            ->color('success')
                            ->requiresConfirmation()
                            ->modalDescription('This will cache the config, routes, and Filament components for better performance.')
                            ->action(fn () => $this->optimizeApplication()),
                    ]),

                Section::make('Database Migrations')
                    ->description($readOnly
                        ? 'Database migrations are managed by the super admin.'
                        : 'Run pending database migrations. This is required after application updates to apply schema changes.')
                    ->icon('heroicon-o-circle-stack')
                    ->footerActions($readOnly ? [] : [
                        Action::make('check_migration_status')
                            ->label('Check Migration Status')
                            ->icon('heroicon-o-magnifying-glass')
                            ->color('info')
                            ->action(fn () => $this->checkMigrationStatus()),
                        Action::make('run_migrations')
                            ->label('Run Pending Migrations')
                            ->icon('heroicon-o-play')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('Run Database Migrations')
                            ->modalDescription('This will apply any pending database migrations. Make sure you have a backup before proceeding.')
                            ->action(fn () => $this->runMigrations()),
                    ]),

                Section::make('Database Seeders')
                    ->description($readOnly
                        ? 'Database seeders are managed by the super admin.'
                        : 'Run database seeders to populate or refresh reference data. Select which seeders to run.')
                    ->icon('heroicon-o-table-cells')
                    ->footerActions($readOnly ? [] : [
                        Action::make('run_seeders')
                            ->label('Run Selected Seeders')
                            ->icon('heroicon-o-play')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('Run Database Seeders')
                            ->modalDescription('This will run the selected seeders. Existing data will be updated (uses updateOrCreate).')
                            ->form([
                                CheckboxList::make('seeders')
                                    ->label('Select Seeders')
                                    ->options([
                                        'reference_data' => 'Reference Data (product databases, raw materials)',
                                        'pdf_settings' => 'PDF Settings (reset to defaults)',
                                        'app_settings' => 'App Settings (reset to defaults)',
                                        'environment_settings' => 'Environment Settings (reset to defaults)',
                                    ])
                                    ->required()
                                    ->helperText('Choose which seeders to run. Reference data includes MBSDB, SSDB, and raw materials.'),
                            ])
                            ->action(fn (array $data) => $this->runSeeders($data['seeders'])),
                    ]),

                Section::make('Storage')
                    ->description($readOnly
                        ? 'Storage management is handled by the super admin.'
                        : 'Manage storage links and file system operations.')
                    ->icon('heroicon-o-folder')
                    ->footerActions($readOnly ? [] : [
                        Action::make('create_storage_link')
                            ->label('Create Storage Link')
                            ->icon('heroicon-o-link')
                            ->color('info')
                            ->requiresConfirmation()
                            ->modalDescription('This will create a symbolic link from public/storage to storage/app/public.')
                            ->action(fn () => $this->createStorageLink()),
                    ]),
            ]);
    }

    public function clearApplicationCache(): void
    {
        try {
            Artisan::call('cache:clear');

            Notification::make()
                ->success()
                ->title('Application cache cleared')
                ->body('All cached data has been flushed.')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Failed to clear application cache')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function clearConfigCache(): void
    {
        $this->runArtisanInProcess('config:clear');

        Notification::make()
            ->success()
            ->title('Configuration cache cleared')
            ->body('Config will be re-read from files on the next request.')
            ->send();
    }

    public function clearRouteCache(): void
    {
        Artisan::call('route:clear');

        Notification::make()
            ->success()
            ->title('Route cache cleared')
            ->body('Routes will be re-compiled on the next request.')
            ->send();
    }

    public function clearViewCache(): void
    {
        Artisan::call('view:clear');

        Notification::make()
            ->success()
            ->title('View cache cleared')
            ->body('Compiled Blade views have been removed.')
            ->send();
    }

    public function clearAllCaches(): void
    {
        try {
            Artisan::call('cache:clear');
        } catch (\Throwable) {
            // Cache clear may fail if store is unavailable
        }

        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('event:clear');

        // config:clear runs in a separate process so it does not
        // disrupt the in-memory config that encryption depends on.
        $this->runArtisanInProcess('config:clear');

        Notification::make()
            ->success()
            ->title('All caches cleared')
            ->body('Application, config, route, view, and event caches have been flushed.')
            ->send();
    }

    public function optimizeApplication(): void
    {
        try {
            // config:cache runs in a separate process because it replaces
            // the in-memory config repository, which would break encryption
            // and session handling for the remainder of this HTTP request.
            $configResult = $this->runArtisanInProcess('config:cache');

            if (! $configResult['successful']) {
                throw new \RuntimeException('config:cache failed: '.$configResult['output']);
            }

            // These are safe to run in-process — they only write cache
            // files and do not affect the current request's state.
            Artisan::call('event:cache');
            Artisan::call('view:cache');
            Artisan::call('filament:optimize');
            Artisan::call('icons:cache');

            Notification::make()
                ->success()
                ->title('Application optimized')
                ->body('Config, events, views, and Filament components have been cached.')
                ->send();
        } catch (\Throwable $e) {
            // If optimize fails, clear caches to avoid leaving a broken state.
            $this->runArtisanInProcess('config:clear');

            Notification::make()
                ->danger()
                ->title('Optimization failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function checkMigrationStatus(): void
    {
        Artisan::call('migrate:status');
        $output = Artisan::output();

        $pending = substr_count($output, 'Pending');

        if ($pending > 0) {
            Notification::make()
                ->warning()
                ->title("{$pending} pending migration(s)")
                ->body('There are database migrations waiting to be run. Click "Run Pending Migrations" to apply them.')
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->success()
                ->title('All migrations are up to date')
                ->body('No pending migrations found.')
                ->send();
        }
    }

    public function runMigrations(): void
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());

            Notification::make()
                ->success()
                ->title('Migrations completed')
                ->body($output !== '' ? $output : 'All migrations have been run successfully.')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Migration failed')
                ->body($e->getMessage())
                ->persistent()
                ->send();
        }
    }

    /**
     * @param  array<int, string>  $seeders
     */
    public function runSeeders(array $seeders): void
    {
        $seederMap = [
            'reference_data' => \Database\Seeders\ReferenceDataSeeder::class,
            'pdf_settings' => \Database\Seeders\PdfSettingsSeeder::class,
            'app_settings' => \Database\Seeders\AppSettingsSeeder::class,
            'environment_settings' => \Database\Seeders\EnvironmentSettingsSeeder::class,
        ];

        $ran = [];
        $failed = [];

        foreach ($seeders as $seeder) {
            $class = $seederMap[$seeder] ?? null;

            if ($class === null) {
                continue;
            }

            try {
                Artisan::call('db:seed', [
                    '--class' => $class,
                    '--force' => true,
                ]);
                $ran[] = $seeder;
            } catch (\Throwable $e) {
                $failed[] = "{$seeder}: {$e->getMessage()}";
            }
        }

        if (count($failed) > 0) {
            Notification::make()
                ->danger()
                ->title('Some seeders failed')
                ->body(implode("\n", $failed))
                ->persistent()
                ->send();

            return;
        }

        // Flush relevant caches after seeding
        $this->flushSettingsCaches($ran);

        Notification::make()
            ->success()
            ->title('Seeders completed')
            ->body('Ran: '.implode(', ', array_map(fn ($s) => str($s)->replace('_', ' ')->title(), $ran)))
            ->send();
    }

    public function createStorageLink(): void
    {
        try {
            Artisan::call('storage:link');

            Notification::make()
                ->success()
                ->title('Storage link created')
                ->body('The public/storage symbolic link has been created.')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Failed to create storage link')
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Run an Artisan command in a separate PHP process to avoid
     * mutating the in-memory config of the current request.
     *
     * This is critical for config:cache and config:clear which replace
     * the config repository, breaking encryption and sessions mid-request.
     *
     * @return array{successful: bool, output: string}
     */
    private function runArtisanInProcess(string $command): array
    {
        $result = Process::path(base_path())
            ->run(['php', 'artisan', $command, '--no-interaction']);

        return [
            'successful' => $result->successful(),
            'output' => trim($result->output()."\n".$result->errorOutput()),
        ];
    }

    /**
     * Flush relevant caches after running seeders.
     *
     * @param  array<int, string>  $seeders
     */
    private function flushSettingsCaches(array $seeders): void
    {
        foreach ($seeders as $seeder) {
            match ($seeder) {
                'app_settings' => app(\App\Services\AppSettingsService::class)->flushCache(),
                'environment_settings' => app(\App\Services\EnvironmentService::class)->flushCache(),
                'pdf_settings' => app(\App\Services\Pdf\PdfSettingsService::class)->flushCache(),
                default => null,
            };
        }
    }
}
