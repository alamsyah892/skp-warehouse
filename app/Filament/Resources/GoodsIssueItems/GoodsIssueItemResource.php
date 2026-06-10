<?php

namespace App\Filament\Resources\GoodsIssueItems;

use App\Filament\Resources\GoodsIssueItems\Pages\ListGoodsIssueItems;
use App\Filament\Resources\GoodsIssueItems\Tables\GoodsIssueItemsTable;
use App\Models\GoodsIssueItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GoodsIssueItemResource extends Resource
{
    protected static ?string $model = GoodsIssueItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Cube;

    public static ?int $navigationSort = 4;

    protected static string|UnitEnum|null $navigationGroup = 'Warehouse';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getModelLabel(): string
    {
        return __('goods-issue.goods_issue_items.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('goods-issue.goods_issue_items.label');
    }

    public static function table(Table $table): Table
    {
        return GoodsIssueItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGoodsIssueItems::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('goodsIssue')
            ->with([
                'item:id,code,name,unit',
                'goodsIssue:id,number,description,type,status,created_at,user_id,warehouse_id,company_id,division_id,project_id',
                'goodsIssue.user:id,name',
                'goodsIssue.warehouse:id,code,name',
                'goodsIssue.company:id,code,name,alias',
                'goodsIssue.division:id,code,name',
                'goodsIssue.project:id,code,po_code,name',
            ]);
    }
}
