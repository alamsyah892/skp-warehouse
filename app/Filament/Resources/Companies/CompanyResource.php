<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Filament\Resources\Companies\Schemas\CompanyForm;
use App\Filament\Resources\Companies\Schemas\CompanyInfolist;
use App\Filament\Resources\Companies\Tables\CompaniesTable;
use App\Models\Company;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::BuildingOffice2;
    public static ?int $navigationSort = 1;
    protected static string|UnitEnum|null $navigationGroup = 'General';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CompanyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CompanyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompaniesTable::configure($table);
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
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'view' => ViewCompany::route('/{record}'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query
            ->with([
                'warehouses' => fn($query) => $query->orderBy('name')->orderBy('code'),
                'divisions' => fn($query) => $query->orderBy('name')->orderBy('code'),
                'projects' => fn($query) => $query->orderBy('name')->orderBy('code'),
                'banks' => fn($query) => $query->orderBy('name')->orderBy('code'),
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
            'warehouses' => fn($query) => $query->orderBy('name')->orderBy('code'),
            'divisions' => fn($query) => $query->orderBy('name')->orderBy('code'),
            'projects' => fn($query) => $query->orderBy('name')->orderBy('code'),
            'banks' => fn($query) => $query->orderBy('name')->orderBy('code'),
        ]);

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('Read Company');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('Create Company');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('Read Company', $record);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('Update Company', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('Delete Company', $record);
    }
}
