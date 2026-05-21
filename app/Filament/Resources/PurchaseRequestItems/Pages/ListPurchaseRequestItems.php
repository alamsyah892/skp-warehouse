<?php

namespace App\Filament\Resources\PurchaseRequestItems\Pages;

use App\Enums\PurchaseRequestStatus;
use App\Filament\Resources\PurchaseRequestItems\PurchaseRequestItemResource;
use App\Models\PurchaseRequestItem;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListPurchaseRequestItems extends ListRecords
{
    protected static string $resource = PurchaseRequestItemResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $counts = PurchaseRequestItem::query()
            ->join('purchase_requests', 'purchase_requests.id', '=', 'purchase_request_items.purchase_request_id')
            ->whereNull('purchase_requests.deleted_at')
            ->when(
                auth()->user()?->warehouses()->exists(),
                fn($query) => $query->whereIn('purchase_requests.warehouse_id', auth()->user()->warehouses->pluck('id')),
            )
            ->selectRaw('purchase_requests.status, count(*) as aggregate')
            ->groupBy('purchase_requests.status')
            ->pluck('aggregate', 'status');

        $getStatusBadge = fn(PurchaseRequestStatus $status) => (int) ($counts[$status->value] ?? 0) ?: null;

        return [
            __('purchase-request.status.all') => Tab::make()
                ->icon(Heroicon::Bars4),
            PurchaseRequestStatus::DRAFT->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->whereHas('purchaseRequest', fn($purchaseRequestQuery) => $purchaseRequestQuery->where('status', PurchaseRequestStatus::DRAFT)))
                ->icon(PurchaseRequestStatus::DRAFT->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::DRAFT))
                ->badgeColor(PurchaseRequestStatus::DRAFT->color()),
            PurchaseRequestStatus::CANCELED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->whereHas('purchaseRequest', fn($purchaseRequestQuery) => $purchaseRequestQuery->where('status', PurchaseRequestStatus::CANCELED)))
                ->icon(PurchaseRequestStatus::CANCELED->icon()),
            PurchaseRequestStatus::REQUESTED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->whereHas('purchaseRequest', fn($purchaseRequestQuery) => $purchaseRequestQuery->where('status', PurchaseRequestStatus::REQUESTED)))
                ->icon(PurchaseRequestStatus::REQUESTED->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::REQUESTED))
                ->badgeColor(PurchaseRequestStatus::REQUESTED->color()),
            PurchaseRequestStatus::CHECKED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->whereHas('purchaseRequest', fn($purchaseRequestQuery) => $purchaseRequestQuery->where('status', PurchaseRequestStatus::CHECKED)))
                ->icon(PurchaseRequestStatus::CHECKED->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::CHECKED))
                ->badgeColor(PurchaseRequestStatus::CHECKED->color()),
            PurchaseRequestStatus::APPROVED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->whereHas('purchaseRequest', fn($purchaseRequestQuery) => $purchaseRequestQuery->where('status', PurchaseRequestStatus::APPROVED)))
                ->icon(PurchaseRequestStatus::APPROVED->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::APPROVED))
                ->badgeColor(PurchaseRequestStatus::APPROVED->color()),
            PurchaseRequestStatus::REVIEWED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->whereHas('purchaseRequest', fn($purchaseRequestQuery) => $purchaseRequestQuery->where('status', PurchaseRequestStatus::REVIEWED)))
                ->icon(PurchaseRequestStatus::REVIEWED->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::REVIEWED))
                ->badgeColor(PurchaseRequestStatus::REVIEWED->color()),
            PurchaseRequestStatus::ORDERED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->whereHas('purchaseRequest', fn($purchaseRequestQuery) => $purchaseRequestQuery->where('status', PurchaseRequestStatus::ORDERED)))
                ->icon(PurchaseRequestStatus::ORDERED->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::ORDERED))
                ->badgeColor(PurchaseRequestStatus::ORDERED->color()),
            PurchaseRequestStatus::FINISHED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->whereHas('purchaseRequest', fn($purchaseRequestQuery) => $purchaseRequestQuery->where('status', PurchaseRequestStatus::FINISHED)))
                ->icon(PurchaseRequestStatus::FINISHED->icon()),
        ];
    }
}
