<?php

namespace App\Filament\Resources\GoodsReceives\Pages;

use App\Filament\Resources\GoodsReceives\GoodsReceiveResource;
use App\Enums\GoodsReceiveType;
use App\Models\GoodsReceive;
use Filament\Resources\Pages\CreateRecord;

class CreateGoodsReceive extends CreateRecord
{
    protected static string $resource = GoodsReceiveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $type = GoodsReceiveType::tryFrom((int) ($data['type'] ?? 0));

        if ($type === GoodsReceiveType::MANUAL) {
            $data['purchase_order_id'] = null;

            $data['goodsReceiveItems'] = collect($data['goodsReceiveItems'] ?? [])
                ->map(function (array $item): array {
                    $item['purchase_order_item_id'] = null;

                    return $item;
                })
                ->values()
                ->all();

            return $data;
        }

        GoodsReceive::syncHeaderFromPurchaseOrder($data);
        GoodsReceive::syncGoodsReceiveItemsFromPurchaseOrderItems($data);
        GoodsReceive::validatePurchaseOrderItemsBelongToPurchaseOrder(
            $data['goodsReceiveItems'] ?? [],
            (int) ($data['purchase_order_id'] ?? 0),
        );
        GoodsReceive::validateAllocationQuantities($data['goodsReceiveItems'] ?? []);

        return $data;
    }
}
