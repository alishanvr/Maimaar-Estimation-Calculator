<?php

namespace App\Filament\Widgets;

use App\Models\Estimation;
use App\Services\CurrencyService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentEstimationsWidget extends TableWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Estimations')
            ->query(
                Estimation::query()->latest()->limit(5)
            )
            ->columns([
                TextColumn::make('quote_number')
                    ->label('Quote #')
                    ->placeholder('-'),
                TextColumn::make('building_name')
                    ->label('Building')
                    ->placeholder('-'),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->placeholder('-'),
                TextColumn::make('user.name')
                    ->label('Created By'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'calculated' => 'success',
                        'finalized' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('total_weight_mt')
                    ->label('Weight (MT)')
                    ->numeric(decimalPlaces: 4)
                    ->placeholder('-'),
                TextColumn::make('total_price_aed')
                    ->label('Price ('.app(CurrencyService::class)->getDisplayCurrency().')')
                    ->formatStateUsing(fn ($state): ?string => $state !== null
                        ? number_format($state * app(CurrencyService::class)->getExchangeRate(), 2)
                        : null)
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since(),
            ])
            ->paginated(false);
    }
}
