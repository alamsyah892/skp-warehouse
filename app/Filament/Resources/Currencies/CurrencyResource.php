<?php

namespace App\Filament\Resources\Currencies;

use App\Filament\Resources\Currencies\Pages\CreateCurrency;
use App\Filament\Resources\Currencies\Pages\EditCurrency;
use App\Filament\Resources\Currencies\Pages\ListCurrencies;
use App\Filament\Resources\Currencies\Pages\ViewCurrency;
use App\Filament\Resources\Currencies\Schemas\CurrencyForm;
use App\Filament\Resources\Currencies\Schemas\CurrencyInfolist;
use App\Filament\Resources\Currencies\Tables\CurrenciesTable;
use App\Models\Currency;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::CurrencyDollar;
    public static ?int $navigationSort = 9;
    protected static string|UnitEnum|null $navigationGroup = 'General';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CurrencyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CurrencyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CurrenciesTable::configure($table);
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
            'index' => ListCurrencies::route('/'),
            'create' => CreateCurrency::route('/create'),
            'view' => ViewCurrency::route('/{record}'),
            'edit' => EditCurrency::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
