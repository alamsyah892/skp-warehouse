<?php

namespace App\Filament\Resources\Vendors;

use App\Filament\Resources\Vendors\Pages\CreateVendor;
use App\Filament\Resources\Vendors\Pages\EditVendor;
use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Filament\Resources\Vendors\Pages\ViewVendor;
use App\Filament\Resources\Vendors\Schemas\VendorForm;
use App\Filament\Resources\Vendors\Schemas\VendorInfolist;
use App\Filament\Resources\Vendors\Tables\VendorsTable;
use App\Models\Vendor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::BuildingStorefront;
    public static ?int $navigationSort = 7;
    protected static string|UnitEnum|null $navigationGroup = 'General';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return VendorForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VendorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorsTable::configure($table);
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
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'view' => ViewVendor::route('/{record}'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query
            ->with([
                'itemCategories' => fn($query) => $query->orderBy('name')->orderBy('code'),
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
            ->withCount([
                'itemCategories',
            ])
        ;

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('Read Vendor');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('Create Vendor');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('Read Vendor', $record);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('Update Vendor', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('Delete Vendor', $record);
    }
}
