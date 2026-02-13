<?php

namespace App\Filament\Pages;

use App\Models\DesignConfiguration;
use App\Services\AppSettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
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
class AppSettings extends Page
{
    protected static ?string $title = 'App Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 99;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    private const CATEGORY = 'app_settings';

    public function mount(): void
    {
        $settings = DesignConfiguration::query()
            ->byCategory(self::CATEGORY)
            ->pluck('value', 'key')
            ->toArray();

        $this->form->fill([
            'app_name' => $settings['app_name'] ?? 'Maimaar Estimation Calculator',
            'company_name' => $settings['company_name'] ?? 'Maimaar',
            'app_logo_path' => $settings['app_logo_path'] ?? null,
            'favicon_path' => $settings['favicon_path'] ?? null,
            'primary_color' => $settings['primary_color'] ?? '#3B82F6',
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
                Section::make('Branding')
                    ->description('App name and logo displayed in the frontend navbar, login page, and browser tab.')
                    ->schema([
                        TextInput::make('app_name')
                            ->label('App Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Maimaar Estimation Calculator')
                            ->helperText('Full name shown in browser tab and dashboard.'),
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Maimaar')
                            ->helperText('Short name shown in the navbar and login page heading.'),
                        FileUpload::make('app_logo_path')
                            ->label('App Logo')
                            ->image()
                            ->directory('app-settings')
                            ->disk('public')
                            ->maxSize(2048)
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('50')
                            ->helperText('Displayed in the navbar. Recommended: transparent PNG, max 200x50px.'),
                    ])
                    ->columns(1),

                Section::make('Favicon')
                    ->description('Small icon displayed in browser tabs and bookmarks.')
                    ->schema([
                        FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->acceptedFileTypes(['image/x-icon', 'image/png', 'image/svg+xml', 'image/vnd.microsoft.icon'])
                            ->directory('app-settings')
                            ->disk('public')
                            ->maxSize(512)
                            ->helperText('Upload an ICO, PNG, or SVG file (max 512KB). Recommended: 32x32px or 64x64px.'),
                    ])
                    ->columns(1),

                Section::make('Colors')
                    ->description('Primary brand color used for accents across the frontend.')
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label('Primary Color')
                            ->required()
                            ->helperText('Used for buttons and link accents. Default: #3B82F6 (blue).'),
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

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = [
            'app_name' => $data['app_name'] ?? 'Maimaar Estimation Calculator',
            'company_name' => $data['company_name'] ?? 'Maimaar',
            'app_logo_path' => $data['app_logo_path'] ?? '',
            'favicon_path' => $data['favicon_path'] ?? '',
            'primary_color' => $data['primary_color'] ?? '#3B82F6',
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

        app(AppSettingsService::class)->flushCache();

        Notification::make()
            ->success()
            ->title('App settings saved')
            ->body('Your branding changes will be reflected in the frontend on next page load.')
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
