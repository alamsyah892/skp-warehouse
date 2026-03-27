<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected ?PurchaseOrderStatus $pendingStatus = null;

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

        if ($data['status'] !== $record->status->value) {
            $this->pendingStatus = PurchaseOrderStatus::from($data['status']);
            $data['status'] = $record->status->value;
        }

        PurchaseOrder::syncHeaderFromPurchaseRequestItems($data);
        PurchaseOrder::validateAllocationQuantities($data['purchaseOrderItems'] ?? [], $record->id);

        $record->applyRevision($data);
        $record->hasWatchedFieldChanges($data);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->pendingStatus) {
            $this->record->changeStatus($this->pendingStatus);
            $this->pendingStatus = null;
        }

        $this->refreshFormData([
            'number',
            'info',
            'warehouse_id',
            'company_id',
            'division_id',
            'project_id',
            'warehouse_address_id',
        ]);
    }
}
