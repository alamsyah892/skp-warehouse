<?php

namespace App\Filament\Resources\GoodsIssues\Pages;

use App\Filament\Resources\GoodsIssues\GoodsIssueResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewGoodsIssue extends ViewRecord
{
    protected static string $resource = GoodsIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon(Heroicon::PencilSquare)
                ->button(),
        ];
    }
}
