<?php

namespace App\Filament\Resources\PurchaseOrderItems\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrderItems\PurchaseOrderItemResource;
use App\Models\PurchaseOrderItem;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListPurchaseOrderItems extends ListRecords
{
    protected static string $resource = PurchaseOrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $counts = PurchaseOrderItem::query()
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->whereNull('purchase_orders.deleted_at')
            ->when(
                auth()->user()?->warehouses()->exists(),
                fn($query) => $query->whereIn('purchase_orders.warehouse_id', auth()->user()->warehouses->pluck('id')),
            )
            ->selectRaw('purchase_orders.status, count(*) as aggregate')
            ->groupBy('purchase_orders.status')
            ->pluck('aggregate', 'status');

        $getStatusBadge = fn(PurchaseOrderStatus $status) => (int) ($counts[$status->value] ?? 0) ?: null;

        return [
            __('purchase-order.status.all') => Tab::make()
                ->icon(Heroicon::Bars4),
            PurchaseOrderStatus::DRAFT->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->whereHas('purchaseOrder', fn($purchaseOrderQuery) => $purchaseOrderQuery->where('status', PurchaseOrderStatus::DRAFT)))
                ->icon(PurchaseOrderStatus::DRAFT->icon())
                ->badge($getStatusBadge(PurchaseOrderStatus::DRAFT))
                ->badgeColor(PurchaseOrderStatus::DRAFT->color()),
            PurchaseOrderStatus::CANCELED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->whereHas('purchaseOrder', fn($purchaseOrderQuery) => $purchaseOrderQuery->where('status', PurchaseOrderStatus::CANCELED)))
                ->icon(PurchaseOrderStatus::CANCELED->icon()),
            PurchaseOrderStatus::ORDERED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->whereHas('purchaseOrder', fn($purchaseOrderQuery) => $purchaseOrderQuery->where('status', PurchaseOrderStatus::ORDERED)))
                ->icon(PurchaseOrderStatus::ORDERED->icon())
                ->badge($getStatusBadge(PurchaseOrderStatus::ORDERED))
                ->badgeColor(PurchaseOrderStatus::ORDERED->color()),
            PurchaseOrderStatus::FINISHED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->whereHas('purchaseOrder', fn($purchaseOrderQuery) => $purchaseOrderQuery->where('status', PurchaseOrderStatus::FINISHED)))
                ->icon(PurchaseOrderStatus::FINISHED->icon()),
        ];
    }
}
