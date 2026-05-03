<?php

namespace App\Filament\Resources\StockItems;

use App\Filament\Resources\StockItems\Pages\ListStockItems;
use App\Filament\Resources\StockItems\Tables\StockItemsTable;
use App\Enums\GoodsIssueStatus;
use App\Enums\GoodsReceiveStatus;
use App\Models\GoodsReceiveItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class StockItemResource extends Resource
{
    protected static ?string $model = GoodsReceiveItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ArchiveBox;
    public static ?int $navigationSort = 3;
    protected static string|UnitEnum|null $navigationGroup = 'Warehouse';

    public static function getModelLabel(): string
    {
        return __('stock-item.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('stock-item.model.plural_label');
    }

    public static function table(Table $table): Table
    {
        return StockItemsTable::configure($table);
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
            'index' => ListStockItems::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $receivedStatuses = [
            GoodsReceiveStatus::RECEIVED->value,
            GoodsReceiveStatus::CONFIRMED->value,
        ];

        $issuedSubquery = DB::table('goods_issue_items')
            ->join('goods_issues', 'goods_issues.id', '=', 'goods_issue_items.goods_issue_id')
            ->where('goods_issues.status', GoodsIssueStatus::ISSUED->value)
            ->selectRaw('
                goods_issue_items.item_id,
                goods_issues.warehouse_id,
                goods_issues.company_id,
                goods_issues.division_id,
                goods_issues.project_id,
                coalesce(sum(goods_issue_items.qty), 0) as issued_qty
            ')
            ->groupBy([
                'goods_issue_items.item_id',
                'goods_issues.warehouse_id',
                'goods_issues.company_id',
                'goods_issues.division_id',
                'goods_issues.project_id',
            ]);

        return parent::getEloquentQuery()
            ->join('goods_receives', 'goods_receives.id', '=', 'goods_receive_items.goods_receive_id')
            ->join('items', 'items.id', '=', 'goods_receive_items.item_id')
            ->join('warehouses', 'warehouses.id', '=', 'goods_receives.warehouse_id')
            ->join('companies', 'companies.id', '=', 'goods_receives.company_id')
            ->join('divisions', 'divisions.id', '=', 'goods_receives.division_id')
            ->join('projects', 'projects.id', '=', 'goods_receives.project_id')
            ->leftJoinSub($issuedSubquery, 'issued_summary', function ($join): void {
                $join->on('issued_summary.item_id', '=', 'goods_receive_items.item_id')
                    ->on('issued_summary.warehouse_id', '=', 'goods_receives.warehouse_id')
                    ->on('issued_summary.company_id', '=', 'goods_receives.company_id')
                    ->on('issued_summary.division_id', '=', 'goods_receives.division_id')
                    ->on('issued_summary.project_id', '=', 'goods_receives.project_id');
            })
            ->whereIn('goods_receives.status', $receivedStatuses)
            ->selectRaw('
                min(goods_receive_items.id) as id,
                goods_receive_items.item_id,
                goods_receives.warehouse_id,
                goods_receives.company_id,
                goods_receives.division_id,
                goods_receives.project_id,
                items.code as item_code,
                items.name as item_name,
                items.unit as item_unit,
                warehouses.name as warehouse_name,
                companies.alias as company_alias,
                divisions.name as division_name,
                projects.name as project_name,
                projects.code as project_code,
                projects.po_code as project_po_code,
                coalesce(sum(goods_receive_items.qty), 0) as received_qty,
                coalesce(max(issued_summary.issued_qty), 0) as issued_qty,
                round(coalesce(sum(goods_receive_items.qty), 0) - coalesce(max(issued_summary.issued_qty), 0), 2) as available_qty
            ')
            ->groupBy([
                'goods_receive_items.item_id',
                'goods_receives.warehouse_id',
                'goods_receives.company_id',
                'goods_receives.division_id',
                'goods_receives.project_id',
                'items.code',
                'items.name',
                'items.unit',
                'warehouses.name',
                'companies.alias',
                'divisions.name',
                'projects.name',
                'projects.code',
                'projects.po_code',
            ])
            ->havingRaw('round(coalesce(sum(goods_receive_items.qty), 0) - coalesce(max(issued_summary.issued_qty), 0), 2) != 0');
    }
}
