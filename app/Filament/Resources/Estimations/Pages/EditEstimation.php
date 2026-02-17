<?php

namespace App\Filament\Resources\Estimations\Pages;

use App\Filament\Resources\Estimations\EstimationResource;
use App\Services\Estimation\EstimationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEstimation extends EditRecord
{
    protected static string $resource = EstimationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculate')
                ->label('Recalculate')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('This will re-run the full calculation pipeline and refresh all items and results.')
                ->visible(fn (): bool => ! empty($this->record->input_data))
                ->action(function (): void {
                    app(EstimationService::class)->calculateAndSave($this->record);

                    Notification::make()
                        ->title('Estimation recalculated successfully.')
                        ->success()
                        ->send();

                    $this->redirect(EstimationResource::getUrl('edit', ['record' => $this->record]));
                }),
            DeleteAction::make(),
        ];
    }
}
