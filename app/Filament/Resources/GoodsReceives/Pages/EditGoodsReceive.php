<?php

namespace App\Filament\Resources\GoodsReceives\Pages;

use App\Filament\Resources\GoodsReceives\GoodsReceiveResource;
use App\Enums\GoodsReceiveType;
use App\Models\GoodsReceive;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditGoodsReceive extends EditRecord
{
    protected static string $resource = GoodsReceiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon(Heroicon::Trash)
                ->button(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var GoodsReceive $record */
        $record = $this->record;

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

            $record->applyRevision($data);
            $record->hasWatchedFieldChanges($data);

            return $data;
        }

        GoodsReceive::syncHeaderFromPurchaseOrder($data);
        GoodsReceive::syncGoodsReceiveItemsFromPurchaseOrderItems($data);
        GoodsReceive::validatePurchaseOrderItemsBelongToPurchaseOrder(
            $data['goodsReceiveItems'] ?? [],
            (int) ($data['purchase_order_id'] ?? 0),
        );
        GoodsReceive::validateAllocationQuantities($data['goodsReceiveItems'] ?? [], $record->id);

        $record->applyRevision($data);
        $record->hasWatchedFieldChanges($data);

        return $data;
    }
}
