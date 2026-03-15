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

        if ($record->status !== PurchaseRequest::STATUS_DRAFT) {

            $oldInfo = $record->info ?? '';

            // ambil rev dari info
            preg_match_all('/Rev\.(\d+)/', $oldInfo, $infoMatches);
            $lastInfoRev = !empty($infoMatches[1]) ? max($infoMatches[1]) : 0;

            // ambil rev dari number
            preg_match('/Rev\.(\d+)/', $record->number ?? '', $numberMatch);
            $lastNumberRev = $numberMatch[1] ?? 0;

            // ambil yang terbesar
            $lastRev = max($lastInfoRev, $lastNumberRev);

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
