<?php

namespace App\Filament\Resources\Warehouses;

use App\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Resources\Warehouses\Pages\ViewWarehouse;
use App\Filament\Resources\Warehouses\Schemas\WarehouseForm;
use App\Filament\Resources\Warehouses\Schemas\WarehouseInfolist;
use App\Filament\Resources\Warehouses\Tables\WarehousesTable;
use App\Models\Warehouse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::HomeModern;
    public static ?int $navigationSort = 2;
    protected static string|UnitEnum|null $navigationGroup = 'General';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WarehouseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehousesTable::configure($table);
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
            'index' => ListWarehouses::route('/'),
            'create' => CreateWarehouse::route('/create'),
            'view' => ViewWarehouse::route('/{record}'),
            'edit' => EditWarehouse::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query
            ->with([
                'companies' => fn($query) => $query->orderBy('alias')->orderBy('code'),
                'projects' => fn($query) => $query->orderBy('name')->orderBy('code'),
                'users' => fn($query) => $query->orderByDesc('id'),
            ])
            ->withCount([
                'companies',
                'projects',
                'users',
                'purchaseRequests',
            ])
        ;

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        $query->with([
            'addresses' => fn($query) => $query->orderBy('id')->orderBy('address'),
            'companies' => fn($query) => $query->orderBy('alias')->orderBy('code'),
            'projects' => fn($query) => $query->orderBy('name')->orderBy('code'),
            'users' => fn($query) => $query->orderByDesc('id'),
            'purchaseRequests' => fn($query) => $query->where('created_at', '>=', now()->subMonths(3))->orderByDesc('id'),
        ]);

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('Read Warehouse');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('Create Warehouse');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('Read Warehouse', $record);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('Update Warehouse', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('Delete Warehouse', $record);
    }
}
