<?php

namespace App\Filament\Resources\SsdbProducts;

use App\Filament\Resources\SsdbProducts\Pages\CreateSsdbProduct;
use App\Filament\Resources\SsdbProducts\Pages\EditSsdbProduct;
use App\Filament\Resources\SsdbProducts\Pages\ListSsdbProducts;
use App\Filament\Resources\SsdbProducts\Schemas\SsdbProductForm;
use App\Filament\Resources\SsdbProducts\Tables\SsdbProductsTable;
use App\Models\SsdbProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SsdbProductResource extends Resource
{
    protected static ?string $model = SsdbProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCubeTransparent;

    protected static string|UnitEnum|null $navigationGroup = 'Reference Data';

    protected static ?string $navigationLabel = 'SSDB Products';

    protected static ?string $modelLabel = 'SSDB Product';

    protected static ?string $pluralModelLabel = 'SSDB Products';

    public static function form(Schema $schema): Schema
    {
        return SsdbProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SsdbProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSsdbProducts::route('/'),
            'create' => CreateSsdbProduct::route('/create'),
            'edit' => EditSsdbProduct::route('/{record}/edit'),
        ];
    }
}
