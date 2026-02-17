<?php

namespace App\Filament\Resources\DesignConfigurations;

use App\Filament\Resources\DesignConfigurations\Pages\CreateDesignConfiguration;
use App\Filament\Resources\DesignConfigurations\Pages\EditDesignConfiguration;
use App\Filament\Resources\DesignConfigurations\Pages\ListDesignConfigurations;
use App\Filament\Resources\DesignConfigurations\Schemas\DesignConfigurationForm;
use App\Filament\Resources\DesignConfigurations\Tables\DesignConfigurationsTable;
use App\Models\DesignConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DesignConfigurationResource extends Resource
{
    protected static ?string $model = DesignConfiguration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Reference Data';

    protected static ?string $navigationLabel = 'Design Configurations';

    public static function form(Schema $schema): Schema
    {
        return DesignConfigurationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DesignConfigurationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDesignConfigurations::route('/'),
            'create' => CreateDesignConfiguration::route('/create'),
            'edit' => EditDesignConfiguration::route('/{record}/edit'),
        ];
    }
}
