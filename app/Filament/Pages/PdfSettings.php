<?php

namespace App\Filament\Pages;

use App\Models\DesignConfiguration;
use App\Services\Pdf\PdfSettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
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
use UnitEnum;

/**
 * @property-read Schema $form
 */
class PdfSettings extends Page
{
    protected static ?string $title = 'PDF Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    private const CATEGORY = 'pdf_settings';

    public function mount(): void
    {
        $settings = DesignConfiguration::query()
            ->byCategory(self::CATEGORY)
            ->pluck('value', 'key')
            ->toArray();

        $this->form->fill([
            'company_name' => $settings['company_name'] ?? 'Maimaar Group',
            'company_logo_path' => $settings['company_logo_path'] ?? null,
            'font_family' => $settings['font_family'] ?? 'dejavu-sans',
            'header_color' => $settings['header_color'] ?? '#1e3a5f',
            'body_font_size' => $settings['body_font_size'] ?? '11',
            'body_line_height' => $settings['body_line_height'] ?? '1.4',
            'show_page_numbers' => (bool) ($settings['show_page_numbers'] ?? true),
            'paper_size' => $settings['paper_size'] ?? 'a4',
            'footer_text' => $settings['footer_text'] ?? '',
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
                    ->description('Company logo and name for PDF headers.')
                    ->schema([
                        FileUpload::make('company_logo_path')
                            ->label('Company Logo')
                            ->image()
                            ->directory('pdf-settings')
                            ->disk('public')
                            ->maxSize(2048)
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('400')
                            ->imageResizeTargetHeight('100')
                            ->helperText('Recommended: transparent PNG or JPEG, max 400×100px.'),
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Maimaar Group'),
                    ])
                    ->columns(1),

                Section::make('Typography')
                    ->description('Font and text settings for PDF documents.')
                    ->schema([
                        Select::make('font_family')
                            ->label('Font Family')
                            ->options([
                                'dejavu-sans' => 'DejaVu Sans (Default)',
                                'dejavu-serif' => 'DejaVu Serif',
                                'dejavu-sans-mono' => 'DejaVu Sans Mono',
                                'helvetica' => 'Helvetica',
                                'times' => 'Times',
                                'courier' => 'Courier',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('body_font_size')
                            ->label('Body Font Size (px)')
                            ->numeric()
                            ->required()
                            ->minValue(8)
                            ->maxValue(16)
                            ->suffix('px'),
                        TextInput::make('body_line_height')
                            ->label('Body Line Height')
                            ->numeric()
                            ->required()
                            ->minValue(1.0)
                            ->maxValue(2.0)
                            ->step(0.1),
                    ])
                    ->columns(3),

                Section::make('Appearance')
                    ->description('Colors, page size, and layout options.')
                    ->schema([
                        ColorPicker::make('header_color')
                            ->label('Header / Accent Color')
                            ->required(),
                        Select::make('paper_size')
                            ->label('Default Paper Size')
                            ->options([
                                'a4' => 'A4',
                                'letter' => 'Letter',
                                'legal' => 'Legal',
                            ])
                            ->required()
                            ->native(false),
                        Toggle::make('show_page_numbers')
                            ->label('Show Page Numbers')
                            ->helperText('Display "Page X of Y" in the footer of each PDF page.'),
                    ])
                    ->columns(3),

                Section::make('Footer')
                    ->description('Optional custom text displayed in the center of the PDF footer.')
                    ->schema([
                        TextInput::make('footer_text')
                            ->label('Custom Footer Text')
                            ->maxLength(255)
                            ->placeholder('e.g. Confidential — For Internal Use Only'),
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
            'company_name' => $data['company_name'] ?? 'Maimaar Group',
            'company_logo_path' => $data['company_logo_path'] ?? '',
            'font_family' => $data['font_family'] ?? 'dejavu-sans',
            'header_color' => $data['header_color'] ?? '#1e3a5f',
            'body_font_size' => (string) ($data['body_font_size'] ?? '11'),
            'body_line_height' => (string) ($data['body_line_height'] ?? '1.4'),
            'show_page_numbers' => $data['show_page_numbers'] ? '1' : '0',
            'paper_size' => $data['paper_size'] ?? 'a4',
            'footer_text' => $data['footer_text'] ?? '',
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

        app(PdfSettingsService::class)->flushCache();

        Notification::make()
            ->success()
            ->title('PDF settings saved')
            ->body('Your changes will be reflected in the next PDF export.')
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
