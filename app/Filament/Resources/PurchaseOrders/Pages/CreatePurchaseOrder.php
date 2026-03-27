<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        PurchaseOrder::syncHeaderFromPurchaseRequestItems($data);
        PurchaseOrder::validateAllocationQuantities($data['purchaseOrderItems'] ?? []);

        return $data;
    }
}
