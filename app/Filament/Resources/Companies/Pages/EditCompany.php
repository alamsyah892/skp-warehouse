<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon(Heroicon::Trash)
                ->button()
                ->before(function ($record, DeleteAction $action) {
                    if ($record->warehouses()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Company cannot be deleted because it still has Warehouses.')
                            ->danger()
                            ->send()
                        ;
                        $action->cancel();
                    } elseif ($record->divisions()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Company cannot be deleted because it still has Divisions.')
                            ->danger()
                            ->send()
                        ;
                        $action->cancel();
                    } elseif ($record->projects()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Company cannot be deleted because it still has Projects.')
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
