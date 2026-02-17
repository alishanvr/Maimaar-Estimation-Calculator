<?php

namespace App\Filament\Pages;

use App\Models\DesignConfiguration;
use App\Services\CurrencyService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class CurrencySettings extends Page
{
    protected static ?string $title = 'Currency Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 101;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    private const CATEGORY = 'currency_settings';

    public function mount(): void
    {
        $settings = DesignConfiguration::query()
            ->byCategory(self::CATEGORY)
            ->pluck('value', 'key')
            ->toArray();

        $manualOverrides = json_decode($settings['manual_overrides'] ?? '{}', true) ?: [];

        $this->form->fill([
            'display_currency' => $settings['display_currency'] ?? 'AED',
            'manual_overrides' => $manualOverrides,
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
        $currencyService = app(CurrencyService::class);
        $lastUpdated = $currencyService->getRatesLastUpdated();

        return $schema
            ->components([
                Section::make('Display Currency')
                    ->description('Select the default currency for displaying prices across the application. All stored values remain in AED and are converted at display time.')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Select::make('display_currency')
                            ->label('Default Currency')
                            ->options(CurrencyService::supportedCurrencies())
                            ->required()
                            ->searchable()
                            ->helperText('Prices will be converted from AED to this currency using the exchange rate below.'),
                    ])
                    ->columns(1),

                Section::make('Exchange Rates')
                    ->description('Exchange rates are fetched daily from an external API. You can override specific rates manually below.')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Placeholder::make('rates_last_updated')
                            ->label('Last Updated')
                            ->content($lastUpdated
                                ? \Carbon\Carbon::parse($lastUpdated)->format('M d, Y h:i A')
                                : 'Never — click "Fetch Latest Rates" to get initial rates.'),
                        Placeholder::make('current_rates')
                            ->label('Current Rates (from AED)')
                            ->content(function () use ($currencyService): string {
                                $rates = $currencyService->getExchangeRates();

                                if (empty($rates)) {
                                    return 'No rates available. Click "Fetch Latest Rates" to get exchange rates.';
                                }

                                $parts = [];
                                foreach ($rates as $code => $rate) {
                                    $parts[] = "{$code}: {$rate}";
                                }

                                return implode(' · ', $parts);
                            }),
                    ])
                    ->columns(1),

                Section::make('Manual Rate Overrides')
                    ->description('Set custom exchange rates here. These take precedence over API-fetched rates. Use the currency code as the key (e.g., USD) and the rate from AED as the value.')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        KeyValue::make('manual_overrides')
                            ->label('')
                            ->keyLabel('Currency Code')
                            ->valueLabel('Rate from AED')
                            ->keyPlaceholder('e.g. USD')
                            ->valuePlaceholder('e.g. 0.2723')
                            ->reorderable(false)
                            ->helperText('Example: 1 AED = 0.2723 USD, so enter "USD" → "0.2723".'),
                    ])
                    ->columns(1),
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
            Action::make('fetch_rates')
                ->label('Fetch Latest Rates')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('This will fetch the latest exchange rates from the API and update stored rates. Manual overrides will be preserved.')
                ->action(function (): void {
                    app(CurrencyService::class)->fetchAndStoreRates();

                    Notification::make()
                        ->success()
                        ->title('Exchange rates updated')
                        ->body('Latest rates have been fetched from the API.')
                        ->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = [
            'display_currency' => $data['display_currency'] ?? 'AED',
            'manual_overrides' => json_encode(
                $this->normalizeOverrides($data['manual_overrides'] ?? [])
            ),
        ];

        foreach ($settings as $key => $value) {
            DesignConfiguration::query()->updateOrCreate(
                ['category' => self::CATEGORY, 'key' => $key],
                [
                    'value' => (string) $value,
                    'label' => str($key)->replace('_', ' ')->title()->toString(),
                ],
            );
        }

        app(CurrencyService::class)->flushCache();

        Notification::make()
            ->success()
            ->title('Currency settings saved')
            ->body('Display currency and rate overrides have been updated.')
            ->send();
    }

    /**
     * Normalize manual overrides: filter empty keys, cast values to float.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, float>
     */
    private function normalizeOverrides(array $overrides): array
    {
        $normalized = [];

        foreach ($overrides as $code => $rate) {
            $code = strtoupper(trim((string) $code));
            $rate = (float) $rate;

            if ($code !== '' && $code !== 'AED' && $rate > 0) {
                $normalized[$code] = $rate;
            }
        }

        return $normalized;
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
