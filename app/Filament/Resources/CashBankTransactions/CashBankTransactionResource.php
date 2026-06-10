<?php

namespace App\Filament\Resources\CashBankTransactions;

use App\Filament\Resources\CashBankTransactions\Pages\CreateCashBankTransaction;
use App\Filament\Resources\CashBankTransactions\Pages\EditCashBankTransaction;
use App\Filament\Resources\CashBankTransactions\Pages\ListCashBankTransactions;
use App\Filament\Resources\CashBankTransactions\Pages\ViewCashBankTransaction;
use App\Filament\Resources\CashBankTransactions\Schemas\CashBankTransactionForm;
use App\Filament\Resources\CashBankTransactions\Schemas\CashBankTransactionInfolist;
use App\Filament\Resources\CashBankTransactions\Tables\CashBankTransactionsTable;
use App\Models\CashBankTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CashBankTransactionResource extends Resource
{
    protected static ?string $model = CashBankTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Banknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Transaksi Kas/Bank';

    protected static ?string $modelLabel = 'Transaksi Kas/Bank';

    protected static ?string $pluralModelLabel = 'Transaksi Kas/Bank';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Schema $schema): Schema
    {
        return CashBankTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CashBankTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashBankTransactionsTable::configure($table);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'description'];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashBankTransactions::route('/'),
            'create' => CreateCashBankTransaction::route('/create'),
            'view' => ViewCashBankTransaction::route('/{record}'),
            'edit' => EditCashBankTransaction::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company', 'project', 'bank', 'vendor'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with(['company', 'project', 'bank', 'vendor'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
