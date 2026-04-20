<?php

namespace App\Filament\Resources\GoodsReceives\Pages;

use App\Filament\Resources\GoodsReceives\GoodsReceiveResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewGoodsReceive extends ViewRecord
{
    protected static string $resource = GoodsReceiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon(Heroicon::PencilSquare)
                ->button(),
        ];
    }
}

