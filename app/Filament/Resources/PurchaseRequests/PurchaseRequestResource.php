<?php

namespace App\Filament\Resources\PurchaseRequests;

use App\Filament\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\ListPurchaseRequests;
use App\Filament\Resources\PurchaseRequests\Pages\ViewPurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestInfolist;
use App\Filament\Resources\PurchaseRequests\Tables\PurchaseRequestsTable;
use App\Models\PurchaseRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PurchaseRequestResource extends Resource
{
    protected static ?string $model = PurchaseRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ClipboardDocumentList;
    public static ?int $navigationSort = 1;
    protected static string|UnitEnum|null $navigationGroup = 'Purchasing';

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Schema $schema): Schema
    {
        return PurchaseRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseRequestsTable::configure($table);
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
            'index' => ListPurchaseRequests::route('/'),
            'create' => CreatePurchaseRequest::route('/create'),
            'view' => ViewPurchaseRequest::route('/{record}'),
            'edit' => EditPurchaseRequest::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        // $query->with(['company', 'warehouse', 'warehouseAddress', 'division', 'project', 'user', 'purchaseRequestItems']);

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('Read Purchase Request');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('Create Purchase Request');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('Read Purchase Request', $record);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('Update Purchase Request', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('Delete Purchase Request', $record);
    }
}
