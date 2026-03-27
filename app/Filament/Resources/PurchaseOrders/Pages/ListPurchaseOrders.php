<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::Plus)
                ->button(),
        ];
    }

    public function getTabs(): array
    {
        $getStatusBadge = function ($status) {
            $count = PurchaseOrder::where('status', $status)->count();
            return $count > 0 ? $count : null;
        };

        return [
            __('purchase-order.status.all') => Tab::make()->icon(Heroicon::Bars4),
            PurchaseOrderStatus::DRAFT->label() => Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', PurchaseOrderStatus::DRAFT))
                ->icon(PurchaseOrderStatus::DRAFT->icon())
                ->badge($getStatusBadge(PurchaseOrderStatus::DRAFT))
                ->badgeColor(PurchaseOrderStatus::DRAFT->color()),
            PurchaseOrderStatus::CANCELED->label() => Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', PurchaseOrderStatus::CANCELED))
                ->icon(PurchaseOrderStatus::CANCELED->icon()),
            PurchaseOrderStatus::ORDERED->label() => Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', PurchaseOrderStatus::ORDERED))
                ->icon(PurchaseOrderStatus::ORDERED->icon())
                ->badge($getStatusBadge(PurchaseOrderStatus::ORDERED))
                ->badgeColor(PurchaseOrderStatus::ORDERED->color()),
            PurchaseOrderStatus::FINISHED->label() => Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->where('status', PurchaseOrderStatus::FINISHED))
                ->icon(PurchaseOrderStatus::FINISHED->icon()),
        ];
    }
}
