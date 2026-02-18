<?php

namespace App\Filament\Resources\Couriers\Pages;

use App\Filament\Resources\Couriers\CourierResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCourier extends ViewRecord
{
    protected static string $resource = CourierResource::class;

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
