<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Enums\PurchaseRequestStatus;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon(Heroicon::Trash)
                ->button()
            ,
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected ?PurchaseRequestStatus $pendingStatus = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->record;
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
        ]);
    }
}
