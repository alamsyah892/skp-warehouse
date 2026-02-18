<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

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
                            ->body('This Project cannot be deleted because it still has Companies.')
                            ->danger()
                            ->send()
                        ;
                        $action->cancel();
                    } elseif ($record->warehouses()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Project cannot be deleted because it still has Warehouses.')
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
