<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon(Heroicon::Trash)
                ->button()
                ->before(function ($record, DeleteAction $action) {
                    if ($record->roles()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This User cannot be deleted because it still has Roles.')
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
