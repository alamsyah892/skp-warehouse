<?php

namespace App\Filament\Resources\GoodsReceiveItems;

use App\Filament\Resources\GoodsReceiveItems\Pages\ListGoodsReceiveItems;
use App\Filament\Resources\GoodsReceiveItems\Tables\GoodsReceiveItemsTable;
use App\Models\GoodsReceiveItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GoodsReceiveItemResource extends Resource
{
    protected static ?string $model = GoodsReceiveItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Cube;

    public static ?int $navigationSort = 2;

    protected static string|UnitEnum|null $navigationGroup = 'Warehouse';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getModelLabel(): string
    {
        return __('goods-receive.goods_receive_items.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('goods-receive.goods_receive_items.label');
    }

    public static function table(Table $table): Table
    {
        return GoodsReceiveItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGoodsReceiveItems::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('goodsReceive')
            ->with([
                'item:id,code,name,unit',
                'purchaseOrderItem:id,purchase_order_id,purchase_request_item_id,description',
                'purchaseOrderItem.purchaseOrder:id,number,description,status,vendor_id',
                'purchaseOrderItem.purchaseOrder.vendor:id,code,name',
                'purchaseOrderItem.purchaseRequestItem:id,purchase_request_id',
                'purchaseOrderItem.purchaseRequestItem.purchaseRequest:id,number',
                'goodsReceive:id,number,description,type,status,created_at,user_id,purchase_order_id,warehouse_id,company_id,division_id,project_id',
                'goodsReceive.user:id,name',
                'goodsReceive.purchaseOrder:id,number',
                'goodsReceive.warehouse:id,code,name',
                'goodsReceive.company:id,code,name,alias',
                'goodsReceive.division:id,code,name',
                'goodsReceive.project:id,code,po_code,name',
            ]);
    }
}
