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
        $record = $this->record;

        $newInfo = $data['info'] ?? null;
        $watchedFieldsChanged = self::watchedFieldsChanged($record, $data);

        if (!$newInfo || !$watchedFieldsChanged) {
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

    public static function watchedFieldsChanged($record, $data): bool
    {
        if (!$record) {
            return false;
        }

        foreach (PurchaseRequest::WATCHED_FIELDS as $field) {
            $old = $record->getOriginal($field);
            $new = $data[$field] ?? null;

            if ((string) $old !== (string) $new) {
                return true;
            }
        }

        // item change detection
        $existing = $record->purchaseRequestItems
            ->map(fn($item) => [
                'item_id' => (int) $item->item_id,
                'qty' => (float) $item->qty,
                'description' => trim((string) $item->description),
            ])
            ->values()
            ->toArray();

        $incoming = collect($data['purchaseRequestItems'] ?? [])
            ->map(fn($item) => [
                'item_id' => (int) ($item['item_id'] ?? 0),
                'qty' => (float) ($item['qty'] ?? 0),
                'description' => trim((string) ($item['description'] ?? '')),
            ])
            ->values()
            ->toArray();

        return $existing !== $incoming;
    }
}
