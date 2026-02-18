<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Vendors\VendorResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditVendor extends EditRecord
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon(Heroicon::Trash)
                ->button()
                ->before(function ($record, DeleteAction $action) {
                    if ($record->itemCategories()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Vendor cannot be deleted because it still has Item categories.')
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
