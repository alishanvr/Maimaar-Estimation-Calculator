<?php

namespace App\Filament\Resources\MbsdbProducts;

use App\Filament\Resources\MbsdbProducts\Pages\CreateMbsdbProduct;
use App\Filament\Resources\MbsdbProducts\Pages\EditMbsdbProduct;
use App\Filament\Resources\MbsdbProducts\Pages\ListMbsdbProducts;
use App\Filament\Resources\MbsdbProducts\Schemas\MbsdbProductForm;
use App\Filament\Resources\MbsdbProducts\Tables\MbsdbProductsTable;
use App\Models\MbsdbProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MbsdbProductResource extends Resource
{
    protected static ?string $model = MbsdbProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Reference Data';

    protected static ?string $navigationLabel = 'MBSDB Products';

    protected static ?string $modelLabel = 'MBSDB Product';

    protected static ?string $pluralModelLabel = 'MBSDB Products';

    public static function form(Schema $schema): Schema
    {
        return MbsdbProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MbsdbProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMbsdbProducts::route('/'),
            'create' => CreateMbsdbProduct::route('/create'),
            'edit' => EditMbsdbProduct::route('/{record}/edit'),
        ];
    }
}
