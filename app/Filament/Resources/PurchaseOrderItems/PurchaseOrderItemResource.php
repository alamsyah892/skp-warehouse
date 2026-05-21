<?php

namespace App\Filament\Resources\PurchaseOrderItems;

use App\Filament\Resources\PurchaseOrderItems\Pages\ListPurchaseOrderItems;
use App\Filament\Resources\PurchaseOrderItems\Tables\PurchaseOrderItemsTable;
use App\Models\PurchaseOrderItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PurchaseOrderItemResource extends Resource
{
    protected static ?string $model = PurchaseOrderItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Squares2x2;
    public static ?int $navigationSort = 4;
    protected static string|UnitEnum|null $navigationGroup = 'Purchasing';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getModelLabel(): string
    {
        return __('purchase-order.purchase_order_items.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('purchase-order.purchase_order_items.label');
    }

    public static function table(Table $table): Table
    {
        return PurchaseOrderItemsTable::configure($table);
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
            'index' => ListPurchaseOrderItems::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withQuantitySummary()
            ->whereHas('purchaseOrder')
            ->with([
                'item:id,code,name,unit',
                'purchaseOrder:id,number,description,status,created_at,user_id,vendor_id,warehouse_id,company_id,division_id,project_id',
                'purchaseOrder.user:id,name',
                'purchaseOrder.vendor:id,code,name',
                'purchaseOrder.warehouse:id,code,name',
                'purchaseOrder.company:id,code,name,alias',
                'purchaseOrder.division:id,code,name',
                'purchaseOrder.project:id,code,po_code,name',
            ]);
    }
}
