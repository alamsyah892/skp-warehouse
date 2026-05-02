<?php

namespace App\Filament\Resources\GoodsReceives\Pages;

use App\Enums\GoodsReceiveStatus;
use App\Filament\Resources\GoodsReceives\GoodsReceiveResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListGoodsReceives extends ListRecords
{
    protected static string $resource = GoodsReceiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::Plus)
                ->button()
            ,
        ];
    }

    public function getTabs(): array
    {
        $getStatusBadge = function (GoodsReceiveStatus $status): ?int {
            $count = GoodsReceiveResource::getEloquentQuery()
                ->where('status', $status)
                ->count();

            return $count > 0 ? $count : null;
        };

        return [
            __('purchase-order.status.all') => Tab::make()->icon(Heroicon::Bars4),
            GoodsReceiveStatus::RECEIVED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', GoodsReceiveStatus::RECEIVED))
                ->icon(GoodsReceiveStatus::RECEIVED->icon())
                ->badge($getStatusBadge(GoodsReceiveStatus::RECEIVED))
                ->badgeColor(GoodsReceiveStatus::RECEIVED->color())
            ,
            GoodsReceiveStatus::CANCELED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', GoodsReceiveStatus::CANCELED))
                ->icon(GoodsReceiveStatus::CANCELED->icon())
            // ->badge($getStatusBadge(GoodsReceiveStatus::CANCELED))
            // ->badgeColor(GoodsReceiveStatus::CANCELED->color())
            ,
            GoodsReceiveStatus::RETURNED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', GoodsReceiveStatus::RETURNED))
                ->icon(GoodsReceiveStatus::RETURNED->icon())
            // ->badge($getStatusBadge(GoodsReceiveStatus::RETURNED))
            // ->badgeColor(GoodsReceiveStatus::RETURNED->color())
            ,
            GoodsReceiveStatus::CONFIRMED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', GoodsReceiveStatus::CONFIRMED))
                ->icon(GoodsReceiveStatus::CONFIRMED->icon())
            // ->badge($getStatusBadge(GoodsReceiveStatus::CONFIRMED))
            // ->badgeColor(GoodsReceiveStatus::CONFIRMED->color())
            ,
        ];
    }
}
