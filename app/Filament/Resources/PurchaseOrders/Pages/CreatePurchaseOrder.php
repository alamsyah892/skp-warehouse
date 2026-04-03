<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected array $selectedPurchaseRequestIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedPurchaseRequestIds = PurchaseOrder::normalizePurchaseRequestIds($this->data['purchaseRequests'] ?? []);
        $data['purchaseRequests'] = $this->selectedPurchaseRequestIds;

        PurchaseOrder::syncHeaderFromPurchaseRequests($data);
        PurchaseOrder::syncPurchaseOrderItemsFromPurchaseRequestItems($data);
        PurchaseOrder::syncTaxTotals($data);
        PurchaseOrder::validateItemsBelongToPurchaseRequests(
            $data['purchaseOrderItems'] ?? [],
            $this->selectedPurchaseRequestIds,
        );
        PurchaseOrder::validateAllocationQuantities($data['purchaseOrderItems'] ?? []);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->purchaseRequests()->sync($this->selectedPurchaseRequestIds);
    }
}
