<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->record;

        if ($record->status !== 'draft') {

            $oldInfo = $record->info ?? '';

            preg_match_all('/Rev\.(\d+)/', $oldInfo, $matches);
            $lastRev = !empty($matches[1]) ? max($matches[1]) : 0;

            $newRev = $lastRev + 1;
            $revNumber = str_pad($newRev, 2, '0', STR_PAD_LEFT);

            $newLine = "Rev.{$revNumber} - {$data['info']}";

            $data['info'] = trim($oldInfo . "\n" . $newLine);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->refreshFormData([
            'number',
            'info',
        ]);
    }
}
