<?php

namespace App\Filament\Resources\GoodsIssues\Pages;

use App\Enums\GoodsIssueStatus;
use App\Filament\Resources\GoodsIssues\GoodsIssueResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditGoodsIssue extends EditRecord
{
    protected static string $resource = GoodsIssueResource::class;

    protected ?GoodsIssueStatus $pendingStatus = null;

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
