<?php

namespace App\Filament\Resources\AnalyticsMetrics\Pages;

use App\Filament\Resources\AnalyticsMetrics\AnalyticsMetricResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;

class ListAnalyticsMetrics extends ListRecords
{
    protected static string $resource = AnalyticsMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aggregate')
                ->label('Run Aggregation Now')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(function (): void {
                    Artisan::call('analytics:aggregate');

                    Notification::make()
                        ->title('Analytics aggregation completed.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Pre-computed analytics metrics aggregated from estimation data. '
            .'Runs daily via scheduled task, or manually via the button above.';
    }
}
