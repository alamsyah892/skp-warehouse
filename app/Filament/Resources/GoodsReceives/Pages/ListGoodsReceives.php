<?php

namespace App\Filament\Resources\GoodsReceives\Pages;

use App\Filament\Resources\GoodsReceives\GoodsReceiveResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGoodsReceives extends ListRecords
{
    protected static string $resource = GoodsReceiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
