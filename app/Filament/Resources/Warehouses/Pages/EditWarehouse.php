<?php

namespace App\Filament\Resources\Warehouses\Pages;

use App\Filament\Resources\Warehouses\WarehouseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon(Heroicon::Trash)
                ->button()
                ->before(function ($record, DeleteAction $action) {
                    if ($record->companies()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Warehouse cannot be deleted because it still has Companies.')
                            ->danger()
                            ->send()
                        ;
                        $action->cancel();
                    } elseif ($record->projects()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Warehouse cannot be deleted because it still has Projects.')
                            ->danger()
                            ->send()
                        ;
                        $action->cancel();
                    }
                })
            ,
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
