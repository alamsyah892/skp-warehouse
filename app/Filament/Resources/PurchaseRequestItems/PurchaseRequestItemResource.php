<?php

namespace App\Filament\Resources\PurchaseRequestItems;

use App\Filament\Resources\PurchaseRequestItems\Pages\ListPurchaseRequestItems;
use App\Filament\Resources\PurchaseRequestItems\Tables\PurchaseRequestItemsTable;
use App\Models\PurchaseRequestItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PurchaseRequestItemResource extends Resource
{
    protected static ?string $model = PurchaseRequestItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Cube;
    public static ?int $navigationSort = 2;
    protected static string|UnitEnum|null $navigationGroup = 'Purchasing';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getModelLabel(): string
    {
        return __('purchase-request.purchase_request_items.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('purchase-request.purchase_request_items.label');
    }

    public static function table(Table $table): Table
    {
        return PurchaseRequestItemsTable::configure($table);
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
            'index' => ListPurchaseRequestItems::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withQuantitySummary()
            ->whereHas('purchaseRequest')
            ->with([
                'item:id,code,name,unit',
                'purchaseRequest:id,number,description,status,created_at,user_id,warehouse_id,company_id,division_id,project_id',
                'purchaseRequest.user:id,name',
                'purchaseRequest.warehouse:id,code,name',
                'purchaseRequest.company:id,code,name,alias',
                'purchaseRequest.division:id,code,name',
                'purchaseRequest.project:id,code,po_code,name',
            ]);
    }
}
