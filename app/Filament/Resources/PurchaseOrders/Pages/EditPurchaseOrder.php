<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected ?PurchaseOrderStatus $pendingStatus = null;
    protected array $selectedPurchaseRequestIds = [];
    public array $initialPurchaseRequestStatusSnapshot = [];
    public array $initialPurchaseRequestItemSnapshots = [];

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
        $record = $this->record;
        $this->selectedPurchaseRequestIds = PurchaseOrder::normalizePurchaseRequestIds($this->data['purchaseRequests'] ?? []);
        $data['purchaseRequests'] = $this->selectedPurchaseRequestIds;
        $purchaseRequestStatusSnapshot = $this->initialPurchaseRequestStatusSnapshot !== []
            ? $this->initialPurchaseRequestStatusSnapshot
            : (array) ($this->data['purchaseRequestStatusSnapshot'] ?? []);
        $purchaseOrderItemsState = collect((array) ($this->data['purchaseOrderItems'] ?? []))
            ->map(function (array $item): array {
                $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);
                $snapshot = $this->initialPurchaseRequestItemSnapshots[$purchaseRequestItemId] ?? null;

                if ($snapshot) {
                    $item['request_qty_snapshot'] = $snapshot['request_qty'] ?? null;
                    $item['ordered_qty_snapshot'] = $snapshot['ordered_qty'] ?? null;
                }

                return $item;
            })
            ->values()
            ->all()
        ;

        // $selectedStatus = filled($data['status'] ?? null) ? (int) $data['status'] : null;

        // if ($selectedStatus !== $record->status->value) {
        //     $this->pendingStatus = PurchaseOrderStatus::from($selectedStatus);
        //     $data['status'] = $record->status->value;
        // }

        PurchaseOrder::syncHeaderFromPurchaseRequests($data);
        PurchaseOrder::syncPurchaseOrderItemsFromPurchaseRequestItems($data);
        PurchaseOrder::syncTaxTotals($data);
        PurchaseOrder::validatePurchaseRequestSynchronization(
            $this->selectedPurchaseRequestIds,
            $purchaseRequestStatusSnapshot,
            $purchaseOrderItemsState,
            $record->id,
        );
        PurchaseOrder::validateItemsBelongToPurchaseRequests(
            $data['purchaseOrderItems'] ?? [],
            $this->selectedPurchaseRequestIds,
        );
        PurchaseOrder::validateManualItems($data['purchaseOrderItems'] ?? []);
        PurchaseOrder::validateAllocationQuantities($data['purchaseOrderItems'] ?? [], $record->id);

        $record->applyRevision($data);

        $record->hasWatchedFieldChanges($data);

        // return Arr::except($data, ['purchaseRequestStatusSnapshot']);
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var PurchaseOrder $record */
        return DB::transaction(function () use ($record, $data): Model {
            $record->update($data);
            $record->purchaseRequests()->sync($this->selectedPurchaseRequestIds);
            $record->syncCalculatedTotals();

            return $record->fresh();
        });
    }

    protected function afterSave(): void
    {
        if ($this->pendingStatus) {
            if ($this->pendingStatus === PurchaseOrderStatus::ORDERED) {
                $this->record->markAsOrdered();
            } else {
                $this->record->changeStatus($this->pendingStatus);
            }

            $this->pendingStatus = null;
        }

        $this->fillForm();
    }

    protected function afterFill(): void
    {
        $this->captureInitialSynchronizationSnapshots();
    }

    protected function captureInitialSynchronizationSnapshots(): void
    {
        $this->record->loadMissing([
            'purchaseRequests:id',
            'purchaseOrderItems:id,purchase_order_id,purchase_request_item_id',
        ]);

        $purchaseRequestIds = $this->record->purchaseRequests
            ->pluck('id')
            ->map(fn(mixed $id): int => (int) $id)
            ->all();

        $this->initialPurchaseRequestStatusSnapshot = PurchaseOrder::buildPurchaseRequestStatusSnapshot($purchaseRequestIds);

        $purchaseRequestItemIds = $this->record->purchaseOrderItems
            ->pluck('purchase_request_item_id')
            ->filter()
            ->map(fn($id): int => (int) $id)
            ->filter(fn(int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $this->initialPurchaseRequestItemSnapshots = PurchaseOrder::buildPurchaseRequestItemSnapshots(
            $purchaseRequestItemIds,
            $this->record->id,
        );
    }
}
