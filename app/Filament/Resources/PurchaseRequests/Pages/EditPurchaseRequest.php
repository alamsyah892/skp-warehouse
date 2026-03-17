<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $info = $data['info'] ?? null;
        if (!$info) {
            return $data;
        }

        $record = $this->record;

        $changedWatchedField = false;
        foreach (PurchaseRequest::WATCHED_FIELDS as $field) {
            $old = $record->getOriginal($field);
            $new = $data[$field] ?? null;

            if ((string) $old !== (string) $new) {
                $changedWatchedField = true;
                break;
            }
        }

        $itemsChanged = $this->itemsChanged($data);

        if (!$changedWatchedField && !$itemsChanged) {
            $data['info'] = $record->info;
            return $data;
        }

        $oldInfo = $record->info ?? '';

        preg_match('/-Rev\.(\d+)$/', $record->number ?? '', $numberMatch);
        $lastNumberRev = $numberMatch[1] ?? 0;

        $newRev = $lastNumberRev + 1;
        $revNumber = str_pad($newRev, 2, '0', STR_PAD_LEFT);

        $newLine = "Rev.{$revNumber} - {$data['info']}";

        $data['info'] = trim($oldInfo . "\n" . $newLine);

        // revision number
        $record->number = $record->incrementRevision();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->refreshFormData([
            'number',
            'info',
        ]);
    }

    protected function itemsChanged(array $data): bool
    {
        $record = $this->record;

        // item change detection
        $existing = $record->purchaseRequestItems
            ->map(fn($item) => [
                'item_id' => (string) $item->item_id,
                'qty' => (string) $item->qty,
                'description' => (string) $item->description,
            ])
            ->values()
            ->toArray();

        $incoming = collect($data['purchaseRequestItems'] ?? [])
            ->map(fn($item) => [
                'item_id' => (string) ($item['item_id'] ?? null),
                'qty' => (string) ($item['qty'] ?? null),
                'description' => (string) ($item['description'] ?? null),
            ])
            ->values()
            ->toArray();

        return $existing !== $incoming;
    }
}
