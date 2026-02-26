<?php

namespace App\Filament\Resources\Items;

use App\Filament\Resources\Items\Pages\CreateItem;
use App\Filament\Resources\Items\Pages\EditItem;
use App\Filament\Resources\Items\Pages\ListItems;
use App\Filament\Resources\Items\Pages\ViewItem;
use App\Filament\Resources\Items\Schemas\ItemForm;
use App\Filament\Resources\Items\Schemas\ItemInfolist;
use App\Filament\Resources\Items\Tables\ItemsTable;
use App\Models\Item;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

set_time_limit(60);

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Cube;
    public static ?int $navigationSort = 6;
    protected static string|UnitEnum|null $navigationGroup = 'General';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemsTable::configure($table);
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
            'index' => ListItems::route('/'),
            'create' => CreateItem::route('/create'),
            'view' => ViewItem::route('/{record}'),
            'edit' => EditItem::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query
            ->withCount([
                'purchaseRequestItems',
                // 'purchaseRequestItems' => fn($query) => $query->where('created_at', '>=', now()->subMonths(3)),
            ])
        ;

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        $query
            ->with([
                'purchaseRequestItems' => fn($query) => $query->orderByDesc('purchase_request_id'),
                // 'purchaseRequestItems' => fn($query) => $query->where('created_at', '>=', now()->subMonths(3))->orderByDesc('purchase_request_id'),
            ])
        ;

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('Read Item');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('Create Item');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('Read Item', $record);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('Update Item', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('Delete Item', $record);
    }
}
