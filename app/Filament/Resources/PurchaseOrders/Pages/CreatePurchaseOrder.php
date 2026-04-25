<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected array $selectedPurchaseRequestIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedPurchaseRequestIds = PurchaseOrder::normalizePurchaseRequestIds($this->data['purchaseRequests'] ?? []);
        $data['purchaseRequests'] = $this->selectedPurchaseRequestIds;
        $purchaseRequestStatusSnapshot = (array) ($this->data['purchaseRequestStatusSnapshot'] ?? []);
        $purchaseOrderItemsState = array_values((array) ($this->data['purchaseOrderItems'] ?? []));

        PurchaseOrder::syncHeaderFromPurchaseRequests($data);
        PurchaseOrder::syncPurchaseOrderItemsFromPurchaseRequestItems($data);
        PurchaseOrder::syncTaxTotals($data);
        PurchaseOrder::validatePurchaseRequestSynchronization(
            $this->selectedPurchaseRequestIds,
            $purchaseRequestStatusSnapshot,
            $purchaseOrderItemsState,
            null,
        );
        PurchaseOrder::validateItemsBelongToPurchaseRequests(
            $data['purchaseOrderItems'] ?? [],
            $this->selectedPurchaseRequestIds,
        );
        PurchaseOrder::validateDuplicatePurchaseRequestItemSources($data['purchaseOrderItems'] ?? []);
        PurchaseOrder::validateManualItems($data['purchaseOrderItems'] ?? []);
        PurchaseOrder::validateAllocationQuantities($data['purchaseOrderItems'] ?? []);

        return Arr::except($data, ['purchaseRequestStatusSnapshot']);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $record = PurchaseOrder::query()->create($data);
            $record->purchaseRequests()->sync($this->selectedPurchaseRequestIds);
            $record->syncCalculatedTotals();

            return $record->fresh();
        });
    }
}
