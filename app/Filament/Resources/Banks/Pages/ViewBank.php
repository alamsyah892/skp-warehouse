<?php

namespace App\Filament\Resources\Banks\Pages;

use App\Filament\Resources\Banks\BankResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewBank extends ViewRecord
{
    protected static string $resource = BankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon(Heroicon::PencilSquare)
                ->button()
            ,
        ];
    }
}
