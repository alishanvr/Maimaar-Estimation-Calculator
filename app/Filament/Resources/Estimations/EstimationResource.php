<?php

namespace App\Filament\Resources\Estimations;

use App\Filament\Resources\Estimations\Pages\CreateEstimation;
use App\Filament\Resources\Estimations\Pages\EditEstimation;
use App\Filament\Resources\Estimations\Pages\ListEstimations;
use App\Filament\Resources\Estimations\Pages\ViewEstimation;
use App\Filament\Resources\Estimations\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Estimations\Schemas\EstimationForm;
use App\Filament\Resources\Estimations\Schemas\EstimationInfolist;
use App\Filament\Resources\Estimations\Tables\EstimationsTable;
use App\Models\Estimation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EstimationResource extends Resource
{
    protected static ?string $model = Estimation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    public static function form(Schema $schema): Schema
    {
        return EstimationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EstimationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EstimationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEstimations::route('/'),
            'create' => CreateEstimation::route('/create'),
            'view' => ViewEstimation::route('/{record}'),
            'edit' => EditEstimation::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
