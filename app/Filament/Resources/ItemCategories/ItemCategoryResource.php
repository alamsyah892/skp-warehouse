<?php

namespace App\Filament\Resources\ItemCategories;

use App\Filament\Resources\ItemCategories\Pages\CreateItemCategory;
use App\Filament\Resources\ItemCategories\Pages\EditItemCategory;
use App\Filament\Resources\ItemCategories\Pages\ListItemCategories;
use App\Filament\Resources\ItemCategories\Pages\ViewItemCategory;
use App\Filament\Resources\ItemCategories\Schemas\ItemCategoryForm;
use App\Filament\Resources\ItemCategories\Schemas\ItemCategoryInfolist;
use App\Filament\Resources\ItemCategories\Tables\ItemCategoriesTable;
use App\Models\ItemCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ItemCategoryResource extends Resource
{
    protected static ?string $model = ItemCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Swatch;
    public static ?int $navigationSort = 5;
    protected static string|UnitEnum|null $navigationGroup = 'General';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ItemCategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ItemCategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListItemCategories::route('/'),
            'create' => CreateItemCategory::route('/create'),
            'view' => ViewItemCategory::route('/{record}'),
            'edit' => EditItemCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query
            ->with([
                'items' => fn($query) => $query->orderBy('name')->orderBy('code'),
            ])
            ->withCount([
                'items',
            ])
        ;

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();
        
        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('Read Item Category');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('Create Item Category');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('Read Item Category', $record);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('Update Item Category', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('Delete Item Category', $record);
    }
}
