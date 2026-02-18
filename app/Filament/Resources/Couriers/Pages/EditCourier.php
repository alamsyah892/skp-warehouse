<?php

namespace App\Filament\Resources\Couriers\Pages;

use App\Filament\Resources\Couriers\CourierResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCourier extends EditRecord
{
    protected static string $resource = CourierResource::class;

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
}
