<?php

namespace App\Filament\Resources\GoodsIssueItems\Pages;

use App\Enums\GoodsIssueStatus;
use App\Filament\Resources\GoodsIssueItems\GoodsIssueItemResource;
use App\Models\GoodsIssueItem;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListGoodsIssueItems extends ListRecords
{
    protected static string $resource = GoodsIssueItemResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $counts = GoodsIssueItem::query()
            ->join('goods_issues', 'goods_issues.id', '=', 'goods_issue_items.goods_issue_id')
            ->whereNull('goods_issues.deleted_at')
            ->when(
                auth()->user()?->warehouses()->exists(),
                fn ($query) => $query->whereIn('goods_issues.warehouse_id', auth()->user()->warehouses->pluck('id')),
            )
            ->selectRaw('goods_issues.status, count(*) as aggregate')
            ->groupBy('goods_issues.status')
            ->pluck('aggregate', 'status');

        $getStatusBadge = fn (GoodsIssueStatus $status): ?int => (int) ($counts[$status->value] ?? 0) ?: null;

        return [
            __('goods-issue.status.all') => Tab::make()
                ->icon(Heroicon::Bars4),
            GoodsIssueStatus::ISSUED->label() => Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereHas('goodsIssue', fn ($goodsIssueQuery) => $goodsIssueQuery->where('status', GoodsIssueStatus::ISSUED)))
                ->icon(GoodsIssueStatus::ISSUED->icon())
                ->badge($getStatusBadge(GoodsIssueStatus::ISSUED))
                ->badgeColor(GoodsIssueStatus::ISSUED->color()),
            GoodsIssueStatus::CANCELED->label() => Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereHas('goodsIssue', fn ($goodsIssueQuery) => $goodsIssueQuery->where('status', GoodsIssueStatus::CANCELED)))
                ->icon(GoodsIssueStatus::CANCELED->icon()),
        ];
    }
}
