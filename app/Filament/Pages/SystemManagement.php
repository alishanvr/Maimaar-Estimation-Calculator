<?php

namespace App\Filament\Pages;

use App\Services\StorageAnalyzerService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\HtmlString;
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
        $storage = app(StorageAnalyzerService::class);

        return $schema
            ->components([
                // ──────────────────────────────────────────────────
                // Storage Usage Analysis
                // ──────────────────────────────────────────────────
                Section::make('Storage Usage Analysis')
                    ->description('Disk space breakdown by directory. Identify what is consuming storage.')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        Placeholder::make('storage_overview')
                            ->label('')
                            ->content(new HtmlString($this->buildStorageOverviewHtml($storage))),
                        Placeholder::make('largest_files')
                            ->label('Top 10 Largest Files')
                            ->content(new HtmlString($this->buildLargestFilesHtml($storage))),
                    ])
                    ->collapsible(),

                // ──────────────────────────────────────────────────
                // Log File Management
                // ──────────────────────────────────────────────────
                Section::make('Log File Management')
                    ->description($readOnly
                        ? 'Log files are managed by the super admin. You can view current log file information below.'
                        : 'View and manage application log files. Large log files are a common cause of high storage usage.')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Placeholder::make('log_files_table')
                            ->label('')
                            ->content(new HtmlString($this->buildLogFilesHtml($storage))),
                    ])
                    ->collapsible()
                    ->footerActions($readOnly ? [] : [
                        Action::make('clear_old_logs')
                            ->label('Clear Old Logs (Keep Today)')
                            ->icon('heroicon-o-clock')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('Clear Old Log Files')
                            ->modalDescription('This will delete all log files older than 1 day, keeping only today\'s logs. This action cannot be undone.')
                            ->action(fn () => $this->handleClearOldLogs()),
                        Action::make('clear_logs_by_age')
                            ->label('Clear Logs by Age')
                            ->icon('heroicon-o-funnel')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('Clear Logs by Age')
                            ->form([
                                Select::make('keep_days')
                                    ->label('Keep logs from the last')
                                    ->options([
                                        '0' => 'Delete all logs',
                                        '1' => '1 day',
                                        '3' => '3 days',
                                        '7' => '7 days',
                                        '14' => '14 days',
                                        '30' => '30 days',
                                    ])
                                    ->default('7')
                                    ->required()
                                    ->helperText('Log files older than this will be permanently deleted.'),
                            ])
                            ->action(fn (array $data) => $this->handleClearLogsByAge((int) $data['keep_days'])),
                        Action::make('clear_all_logs')
                            ->label('Clear All Logs')
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalHeading('Clear All Log Files')
                            ->modalDescription('This will permanently delete ALL log files. A new log file will be created automatically on the next request. This action cannot be undone.')
                            ->action(fn () => $this->handleClearAllLogs()),
                    ]),

                // ──────────────────────────────────────────────────
                // Cache & Session File Cleanup
                // ──────────────────────────────────────────────────
                Section::make('Cache & Session File Cleanup')
                    ->description($readOnly
                        ? 'File cleanup is managed by the super admin. Current storage usage is shown below.'
                        : 'Clean up framework-generated files that accumulate over time. These files are automatically regenerated when needed.')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->schema([
                        Placeholder::make('cleanup_overview')
                            ->label('')
                            ->content(new HtmlString($this->buildCleanupOverviewHtml($storage))),
                    ])
                    ->collapsible()
                    ->footerActions($readOnly ? [] : [
                        Action::make('clear_cache_files')
                            ->label('Clear Cache Files')
                            ->icon('heroicon-o-server-stack')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('Clear Framework Cache Files')
                            ->modalDescription('This will delete all files in storage/framework/cache/data/. The file cache will rebuild automatically. This does NOT affect Redis/database caches.')
                            ->action(fn () => $this->handleClearCacheFiles()),
                        Action::make('clear_session_files')
                            ->label('Clear Session Files')
                            ->icon('heroicon-o-user-group')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('Clear Session Files')
                            ->modalDescription('This will delete all session files. All logged-in users (including you) will be logged out. Only affects file-based sessions.')
                            ->visible(fn (): bool => $storage->getSessionDriver() === 'file')
                            ->action(fn () => $this->handleClearSessionFiles()),
                        Action::make('clear_compiled_views')
                            ->label('Clear Compiled Views')
                            ->icon('heroicon-o-eye')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('Clear Compiled Blade Views')
                            ->modalDescription('This will delete all compiled Blade view files from storage/framework/views/. Views are automatically recompiled on next access.')
                            ->action(fn () => $this->handleClearCompiledViews()),
                        Action::make('clear_livewire_tmp')
                            ->label('Clear Livewire Temp')
                            ->icon('heroicon-o-arrow-up-tray')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->modalHeading('Clear Livewire Temp Files')
                            ->modalDescription('This will delete temporary files left from Livewire file uploads. Any in-progress uploads will be interrupted.')
                            ->action(fn () => $this->handleClearLivewireTmp()),
                        Action::make('clear_all_storage_files')
                            ->label('Clear All Framework Files')
                            ->icon('heroicon-o-fire')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalHeading('Clear All Framework Files')
                            ->modalDescription('This will clear cache files, compiled views, Livewire temp files, and session files (if file driver). All users will be logged out if using file sessions. This cannot be undone.')
                            ->action(fn () => $this->handleClearAllStorageFiles()),
                    ]),

                // ──────────────────────────────────────────────────
                // Cache Management (existing)
                // ──────────────────────────────────────────────────
                Section::make('Cache Management')
                    ->description($readOnly
                        ? 'You can view system settings but only the super admin can perform these actions.'
                        : 'Clear various application caches. Use this after making configuration changes or when the application behaves unexpectedly.')
                    ->icon('heroicon-o-trash')
                    ->collapsible()
                    ->collapsed()
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

                // ──────────────────────────────────────────────────
                // Optimize Application (existing)
                // ──────────────────────────────────────────────────
                Section::make('Optimize Application')
                    ->description($readOnly
                        ? 'Application optimization is managed by the super admin.'
                        : 'Cache configuration, routes, and views for better performance in production. No need to clear caches first — this rebuilds them automatically.')
                    ->icon('heroicon-o-bolt')
                    ->collapsible()
                    ->collapsed()
                    ->footerActions($readOnly ? [] : [
                        Action::make('optimize')
                            ->label('Optimize Application')
                            ->icon('heroicon-o-rocket-launch')
                            ->color('success')
                            ->requiresConfirmation()
                            ->modalDescription('This will cache the config, routes, and Filament components for better performance.')
                            ->action(fn () => $this->optimizeApplication()),
                    ]),

                // ──────────────────────────────────────────────────
                // Database Migrations (existing)
                // ──────────────────────────────────────────────────
                Section::make('Database Migrations')
                    ->description($readOnly
                        ? 'Database migrations are managed by the super admin.'
                        : 'Run pending database migrations. This is required after application updates to apply schema changes.')
                    ->icon('heroicon-o-circle-stack')
                    ->collapsible()
                    ->collapsed()
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

                // ──────────────────────────────────────────────────
                // Database Seeders (existing)
                // ──────────────────────────────────────────────────
                Section::make('Database Seeders')
                    ->description($readOnly
                        ? 'Database seeders are managed by the super admin.'
                        : 'Run database seeders to populate or refresh reference data. Select which seeders to run.')
                    ->icon('heroicon-o-table-cells')
                    ->collapsible()
                    ->collapsed()
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

                // ──────────────────────────────────────────────────
                // Storage Links (existing)
                // ──────────────────────────────────────────────────
                Section::make('Storage Links')
                    ->description($readOnly
                        ? 'Storage management is handled by the super admin.'
                        : 'Manage storage links and file system operations.')
                    ->icon('heroicon-o-folder')
                    ->collapsible()
                    ->collapsed()
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

    // ─── HTML Builders for Storage Sections ──────────────────────

    private function buildStorageOverviewHtml(StorageAnalyzerService $storage): string
    {
        $total = $storage->getTotalUsage();
        $breakdown = $storage->getDirectoryBreakdown();

        $html = '<div style="margin-bottom:16px; border-radius:8px; background:rgba(var(--primary-500), 0.08); padding:16px;">';
        $html .= '<div style="display:flex; align-items:center; gap:12px; margin-bottom:4px;">';
        $html .= '<span style="font-size:1.5rem; font-weight:700; color:rgb(var(--primary-500));">'.$total['size_human'].'</span>';
        $html .= '<span style="font-size:0.875rem; opacity:0.6;">total across '.$total['file_count'].' files</span>';
        $html .= '</div></div>';

        $html .= '<div style="overflow-x:auto;">';
        $html .= '<table style="width:100%; font-size:0.875rem; border-collapse:collapse;">';
        $html .= '<thead><tr style="border-bottom:2px solid rgba(128,128,128,0.2);">';
        $html .= '<th style="text-align:left; padding:10px 12px; font-weight:600; opacity:0.6;">Directory</th>';
        $html .= '<th style="text-align:left; padding:10px 12px; font-weight:600; opacity:0.6;">Description</th>';
        $html .= '<th style="text-align:right; padding:10px 12px; font-weight:600; opacity:0.6;">Size</th>';
        $html .= '<th style="text-align:right; padding:10px 12px; font-weight:600; opacity:0.6;">Files</th>';
        $html .= '<th style="padding:10px 12px; font-weight:600; opacity:0.6; width:30%;">Usage</th>';
        $html .= '</tr></thead><tbody>';

        $maxSize = max(array_column($breakdown, 'size')) ?: 1;

        foreach ($breakdown as $dir) {
            $percent = $total['size'] > 0
                ? round(($dir['size'] / $total['size']) * 100, 1)
                : 0;
            $barWidth = $maxSize > 0 ? round(($dir['size'] / $maxSize) * 100) : 0;

            $barColor = match (true) {
                $percent > 50 => '#ef4444',
                $percent > 25 => '#f59e0b',
                default => 'rgb(var(--primary-500))',
            };

            $html .= '<tr style="border-bottom:1px solid rgba(128,128,128,0.1);">';
            $html .= '<td style="padding:10px 12px; font-family:monospace; font-size:0.75rem;">'.e($dir['path']).'</td>';
            $html .= '<td style="padding:10px 12px; font-size:0.75rem; opacity:0.6;">'.e($dir['description']).'</td>';
            $html .= '<td style="padding:10px 12px; text-align:right; font-weight:600;">'.e($dir['size_human']).'</td>';
            $html .= '<td style="padding:10px 12px; text-align:right; opacity:0.7;">'.$dir['file_count'].'</td>';
            $html .= '<td style="padding:10px 12px;">';
            $html .= '<div style="display:flex; align-items:center; gap:8px;">';
            $html .= '<div style="flex:1; height:8px; border-radius:9999px; background:rgba(128,128,128,0.15); overflow:hidden;">';
            $html .= '<div style="height:100%; border-radius:9999px; background:'.$barColor.'; width:'.$barWidth.'%; transition:width 0.3s;"></div>';
            $html .= '</div>';
            $html .= '<span style="font-size:0.75rem; opacity:0.6; min-width:40px; text-align:right;">'.$percent.'%</span>';
            $html .= '</div></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    private function buildLargestFilesHtml(StorageAnalyzerService $storage): string
    {
        $files = $storage->getLargestFiles(10);

        if ($files->isEmpty()) {
            return '<p style="font-size:0.875rem; opacity:0.6;">No files found in storage.</p>';
        }

        $html = '<div style="overflow-x:auto;">';
        $html .= '<table style="width:100%; font-size:0.875rem; border-collapse:collapse;">';
        $html .= '<thead><tr style="border-bottom:2px solid rgba(128,128,128,0.2);">';
        $html .= '<th style="text-align:left; padding:10px 12px; font-weight:600; opacity:0.6; width:30px;">#</th>';
        $html .= '<th style="text-align:left; padding:10px 12px; font-weight:600; opacity:0.6;">File</th>';
        $html .= '<th style="text-align:right; padding:10px 12px; font-weight:600; opacity:0.6;">Size</th>';
        $html .= '<th style="text-align:right; padding:10px 12px; font-weight:600; opacity:0.6;">Modified</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($files as $index => $file) {
            $html .= '<tr style="border-bottom:1px solid rgba(128,128,128,0.1);">';
            $html .= '<td style="padding:10px 12px; opacity:0.4;">'.($index + 1).'</td>';
            $html .= '<td style="padding:10px 12px; font-family:monospace; font-size:0.75rem;">'.e($file['relative_path']).'</td>';
            $html .= '<td style="padding:10px 12px; text-align:right; font-weight:600;">'.e($file['size_human']).'</td>';
            $html .= '<td style="padding:10px 12px; text-align:right; font-size:0.75rem; opacity:0.6;">'.e($file['modified_at']).'</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    private function buildLogFilesHtml(StorageAnalyzerService $storage): string
    {
        $logs = $storage->getLogFiles();

        if ($logs->isEmpty()) {
            return '<div style="border-radius:8px; background:rgba(128,128,128,0.06); padding:16px; text-align:center;">'
                .'<p style="font-size:0.875rem; opacity:0.6;">No log files found. The system is clean.</p>'
                .'</div>';
        }

        $totalSize = $logs->sum('size');
        $logsTotalHuman = $this->formatBytesStatic($totalSize);

        $html = '<div style="margin-bottom:16px; border-radius:8px; background:rgba(128,128,128,0.06); padding:16px;">';
        $html .= '<div style="display:flex; align-items:center; gap:12px;">';
        $html .= '<span style="font-weight:700;">'.$logs->count().' log file(s)</span>';
        $html .= '<span style="opacity:0.4;">&middot;</span>';
        $html .= '<span style="font-size:0.875rem; opacity:0.6;">Total: '.$logsTotalHuman.'</span>';
        $html .= '</div></div>';

        $html .= '<div style="overflow-x:auto;">';
        $html .= '<table style="width:100%; font-size:0.875rem; border-collapse:collapse;">';
        $html .= '<thead><tr style="border-bottom:2px solid rgba(128,128,128,0.2);">';
        $html .= '<th style="text-align:left; padding:10px 12px; font-weight:600; opacity:0.6;">File Name</th>';
        $html .= '<th style="text-align:right; padding:10px 12px; font-weight:600; opacity:0.6;">Size</th>';
        $html .= '<th style="text-align:right; padding:10px 12px; font-weight:600; opacity:0.6;">Last Modified</th>';
        $html .= '<th style="text-align:right; padding:10px 12px; font-weight:600; opacity:0.6;">Age</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($logs as $log) {
            $ageLabel = match (true) {
                $log['age_days'] === 0 => 'Today',
                $log['age_days'] === 1 => 'Yesterday',
                default => $log['age_days'].' days ago',
            };

            $ageStyle = match (true) {
                $log['age_days'] > 30 => 'color:#ef4444; font-weight:600;',
                $log['age_days'] > 7 => 'color:#f59e0b; font-weight:500;',
                default => 'opacity:0.6;',
            };

            $sizeStyle = match (true) {
                $log['size'] > 10_485_760 => 'color:#ef4444; font-weight:700;',
                $log['size'] > 1_048_576 => 'color:#f59e0b; font-weight:600;',
                default => 'font-weight:600;',
            };

            $html .= '<tr style="border-bottom:1px solid rgba(128,128,128,0.1);">';
            $html .= '<td style="padding:10px 12px; font-family:monospace; font-size:0.75rem;">'.e($log['name']).'</td>';
            $html .= '<td style="padding:10px 12px; text-align:right; '.$sizeStyle.'">'.e($log['size_human']).'</td>';
            $html .= '<td style="padding:10px 12px; text-align:right; font-size:0.75rem; opacity:0.6;">'.e($log['modified_at']).'</td>';
            $html .= '<td style="padding:10px 12px; text-align:right; font-size:0.75rem; '.$ageStyle.'">'.e($ageLabel).'</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    private function buildCleanupOverviewHtml(StorageAnalyzerService $storage): string
    {
        $breakdown = $storage->getDirectoryBreakdown();
        $sessionDriver = $storage->getSessionDriver();
        $cacheDriver = $storage->getCacheDriver();

        $items = [
            [
                'label' => 'Framework Cache Files',
                'icon' => '&#128451;',
                'path' => 'storage/framework/cache',
                'driver' => 'Cache driver: '.$cacheDriver,
                'note' => $cacheDriver !== 'file' ? 'Using '.$cacheDriver.' driver — file cache may be empty' : null,
            ],
            [
                'label' => 'Session Files',
                'icon' => '&#128101;',
                'path' => 'storage/framework/sessions',
                'driver' => 'Session driver: '.$sessionDriver,
                'note' => $sessionDriver !== 'file' ? 'Using '.$sessionDriver.' driver — no session files to clean' : null,
            ],
            [
                'label' => 'Compiled Views',
                'icon' => '&#128065;',
                'path' => 'storage/framework/views',
                'driver' => null,
                'note' => null,
            ],
            [
                'label' => 'Livewire Temp Uploads',
                'icon' => '&#128228;',
                'path' => 'storage/app/private',
                'driver' => null,
                'note' => null,
            ],
        ];

        $html = '<div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:16px;">';

        foreach ($items as $item) {
            $dirInfo = collect($breakdown)->firstWhere('path', $item['path']);
            $size = $dirInfo ? $dirInfo['size_human'] : '0 B';
            $fileCount = $dirInfo ? $dirInfo['file_count'] : 0;

            $html .= '<div style="border-radius:8px; border:1px solid rgba(128,128,128,0.2); padding:16px; background:rgba(128,128,128,0.03);">';
            $html .= '<div style="display:flex; align-items:center; gap:8px; font-weight:600; margin-bottom:8px;">';
            $html .= '<span style="font-size:1.2rem;">'.$item['icon'].'</span>';
            $html .= '<span>'.e($item['label']).'</span>';
            $html .= '</div>';
            $html .= '<div style="font-size:1.25rem; font-weight:700; color:rgb(var(--primary-500));">'.$size.'</div>';
            $html .= '<div style="font-size:0.75rem; opacity:0.5; margin-top:2px;">'.$fileCount.' files</div>';

            if ($item['driver']) {
                $html .= '<div style="margin-top:8px; font-size:0.75rem; opacity:0.45; border-top:1px solid rgba(128,128,128,0.15); padding-top:8px;">'.$item['driver'].'</div>';
            }

            if ($item['note']) {
                $html .= '<div style="margin-top:4px; font-size:0.75rem; color:#3b82f6;">'.$item['note'].'</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Format bytes into a human-readable string.
     */
    private function formatBytesStatic(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $exponent = (int) floor(log($bytes, 1024));
        $exponent = min($exponent, count($units) - 1);

        return number_format($bytes / (1024 ** $exponent), 2).' '.$units[$exponent];
    }

    // ─── Storage Cleanup Actions ────────────────────────────────

    public function handleClearOldLogs(): void
    {
        try {
            $result = app(StorageAnalyzerService::class)->clearOldLogs(1);

            activity()
                ->causedBy(auth()->user())
                ->log("Cleared {$result['deleted_count']} old log files, freed {$result['freed_human']}");

            Notification::make()
                ->success()
                ->title('Old log files cleared')
                ->body("Deleted {$result['deleted_count']} file(s), freed {$result['freed_human']}.")
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Failed to clear old logs')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function handleClearLogsByAge(int $keepDays): void
    {
        try {
            $result = app(StorageAnalyzerService::class)->clearOldLogs($keepDays);
            $label = $keepDays === 0 ? 'all' : "older than {$keepDays} day(s)";

            activity()
                ->causedBy(auth()->user())
                ->log("Cleared {$result['deleted_count']} log files ({$label}), freed {$result['freed_human']}");

            Notification::make()
                ->success()
                ->title('Log files cleared')
                ->body("Deleted {$result['deleted_count']} file(s) {$label}, freed {$result['freed_human']}.")
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Failed to clear logs')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function handleClearAllLogs(): void
    {
        try {
            $result = app(StorageAnalyzerService::class)->clearAllLogs();

            activity()
                ->causedBy(auth()->user())
                ->log("Cleared all {$result['deleted_count']} log files, freed {$result['freed_human']}");

            Notification::make()
                ->success()
                ->title('All log files cleared')
                ->body("Deleted {$result['deleted_count']} file(s), freed {$result['freed_human']}.")
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Failed to clear all logs')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function handleClearCacheFiles(): void
    {
        try {
            $result = app(StorageAnalyzerService::class)->clearFrameworkCacheFiles();

            activity()
                ->causedBy(auth()->user())
                ->log("Cleared {$result['deleted_count']} framework cache files, freed {$result['freed_human']}");

            Notification::make()
                ->success()
                ->title('Cache files cleared')
                ->body("Deleted {$result['deleted_count']} file(s), freed {$result['freed_human']}.")
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Failed to clear cache files')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function handleClearSessionFiles(): void
    {
        try {
            $result = app(StorageAnalyzerService::class)->clearSessionFiles();

            activity()
                ->causedBy(auth()->user())
                ->log("Cleared {$result['deleted_count']} session files, freed {$result['freed_human']}");

            Notification::make()
                ->success()
                ->title('Session files cleared')
                ->body("Deleted {$result['deleted_count']} file(s), freed {$result['freed_human']}. All file-based sessions have been invalidated.")
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Failed to clear session files')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function handleClearCompiledViews(): void
    {
        try {
            $result = app(StorageAnalyzerService::class)->clearCompiledViews();

            activity()
                ->causedBy(auth()->user())
                ->log("Cleared {$result['deleted_count']} compiled view files, freed {$result['freed_human']}");

            Notification::make()
                ->success()
                ->title('Compiled views cleared')
                ->body("Deleted {$result['deleted_count']} file(s), freed {$result['freed_human']}. Views will recompile on next access.")
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Failed to clear compiled views')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function handleClearLivewireTmp(): void
    {
        try {
            $result = app(StorageAnalyzerService::class)->clearLivewireTmp();

            activity()
                ->causedBy(auth()->user())
                ->log("Cleared {$result['deleted_count']} Livewire temp files, freed {$result['freed_human']}");

            Notification::make()
                ->success()
                ->title('Livewire temp files cleared')
                ->body("Deleted {$result['deleted_count']} file(s), freed {$result['freed_human']}.")
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Failed to clear Livewire temp files')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function handleClearAllStorageFiles(): void
    {
        try {
            $storage = app(StorageAnalyzerService::class);
            $totalDeleted = 0;
            $totalFreed = 0;

            $results = [
                'cache' => $storage->clearFrameworkCacheFiles(),
                'views' => $storage->clearCompiledViews(),
                'livewire' => $storage->clearLivewireTmp(),
            ];

            if ($storage->getSessionDriver() === 'file') {
                $results['sessions'] = $storage->clearSessionFiles();
            }

            foreach ($results as $result) {
                $totalDeleted += $result['deleted_count'];
                $totalFreed += $result['freed_bytes'];
            }

            $freedHuman = $this->formatBytesStatic($totalFreed);

            activity()
                ->causedBy(auth()->user())
                ->log("Cleared all framework files: {$totalDeleted} files, freed {$freedHuman}");

            Notification::make()
                ->success()
                ->title('All framework files cleared')
                ->body("Deleted {$totalDeleted} file(s) total, freed {$freedHuman}.")
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Failed to clear all framework files')
                ->body($e->getMessage())
                ->send();
        }
    }

    // ─── Existing Cache/Optimize/Migration Actions ──────────────

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
            $configResult = $this->runArtisanInProcess('config:cache');

            if (! $configResult['successful']) {
                throw new \RuntimeException('config:cache failed: '.$configResult['output']);
            }

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

    // ─── Helpers ────────────────────────────────────────────────

    /**
     * Run an Artisan command in a separate PHP process.
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
