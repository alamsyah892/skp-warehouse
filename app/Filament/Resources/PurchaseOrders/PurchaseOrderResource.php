<?php

namespace App\Filament\Resources\PurchaseOrders;

use App\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Filament\Resources\PurchaseOrders\Schemas\PurchaseOrderInfolist;
use App\Filament\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use App\Models\PurchaseOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ShoppingCart;
    public static ?int $navigationSort = 2;
    protected static string|UnitEnum|null $navigationGroup = 'Purchasing';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getModelLabel(): string
    {
        return __('purchase-order.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('purchase-order.model.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseOrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PurchaseOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'view' => ViewPurchaseOrder::route('/{record}'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['purchaseOrderItems'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with([
                'purchaseRequests.warehouseAddress',
                'purchaseRequests.user',
                'purchaseOrderItems.purchaseOrder',
                'purchaseOrderItems.item',
                'purchaseOrderItems.purchaseRequestItem.purchaseRequest',
                'statusLogs' => fn($query) => $query->orderBy('id'),
                'statusLogs.user',
            ])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
