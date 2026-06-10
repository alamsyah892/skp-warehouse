<?php

namespace App\Filament\Resources\GoodsReceiveItems\Pages;

use App\Enums\GoodsReceiveStatus;
use App\Filament\Resources\GoodsReceiveItems\GoodsReceiveItemResource;
use App\Models\GoodsReceiveItem;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListGoodsReceiveItems extends ListRecords
{
    protected static string $resource = GoodsReceiveItemResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $counts = GoodsReceiveItem::query()
            ->join('goods_receives', 'goods_receives.id', '=', 'goods_receive_items.goods_receive_id')
            ->whereNull('goods_receives.deleted_at')
            ->when(
                auth()->user()?->warehouses()->exists(),
                fn ($query) => $query->whereIn('goods_receives.warehouse_id', auth()->user()->warehouses->pluck('id')),
            )
            ->selectRaw('goods_receives.status, count(*) as aggregate')
            ->groupBy('goods_receives.status')
            ->pluck('aggregate', 'status');

        $getStatusBadge = fn (GoodsReceiveStatus $status): ?int => (int) ($counts[$status->value] ?? 0) ?: null;

        return [
            __('goods-receive.status.all') => Tab::make()
                ->icon(Heroicon::Bars4),
            GoodsReceiveStatus::RECEIVED->label() => Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereHas('goodsReceive', fn ($goodsReceiveQuery) => $goodsReceiveQuery->where('status', GoodsReceiveStatus::RECEIVED)))
                ->icon(GoodsReceiveStatus::RECEIVED->icon())
                ->badge($getStatusBadge(GoodsReceiveStatus::RECEIVED))
                ->badgeColor(GoodsReceiveStatus::RECEIVED->color()),
            GoodsReceiveStatus::CANCELED->label() => Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereHas('goodsReceive', fn ($goodsReceiveQuery) => $goodsReceiveQuery->where('status', GoodsReceiveStatus::CANCELED)))
                ->icon(GoodsReceiveStatus::CANCELED->icon()),
            GoodsReceiveStatus::RETURNED->label() => Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereHas('goodsReceive', fn ($goodsReceiveQuery) => $goodsReceiveQuery->where('status', GoodsReceiveStatus::RETURNED)))
                ->icon(GoodsReceiveStatus::RETURNED->icon()),
            GoodsReceiveStatus::CONFIRMED->label() => Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->whereHas('goodsReceive', fn ($goodsReceiveQuery) => $goodsReceiveQuery->where('status', GoodsReceiveStatus::CONFIRMED)))
                ->icon(GoodsReceiveStatus::CONFIRMED->icon()),
        ];
    }
}
