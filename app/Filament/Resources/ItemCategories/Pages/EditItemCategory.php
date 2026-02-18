<?php

namespace App\Filament\Resources\ItemCategories\Pages;

use App\Filament\Resources\ItemCategories\ItemCategoryResource;
use App\Models\ItemCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditItemCategory extends EditRecord
{
    protected static string $resource = ItemCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon(Heroicon::Trash)
                ->button()
                ->before(function ($record, DeleteAction $action) {
                    if (!$record->isLeaf()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Item Category cannot be deleted because it still has Sub Categories.')
                            ->danger()
                            ->send()
                        ;
                        $action->cancel();
                    } elseif ($record->items()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Item Category cannot be deleted because it still has Items.')
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['parent_id'] = match ((int) $data['level']) {
            ItemCategory::LEVEL_DOMAIN => null,
            ItemCategory::LEVEL_CATEGORY => $data['domain_id'],
            ItemCategory::LEVEL_SUB_CATEGORY => $data['category_id'],
        // ItemCategory::LEVEL_FINAL_CATEGORY => $data['sub_category_id'],
        };

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $category = $this->record;

        /**
         * Level 1
         * - Tidak punya parent
         */

        /**
         * Level 2
         * - domain_id = parent_id
         */
        if ($category->level === ItemCategory::LEVEL_CATEGORY) {
            $data['domain_id'] = $category->parent_id;
        }

        /**
         * Level 3
         * - category_id = parent_id
         * - domain_id = parent dari parent
         */
        if ($category->level === ItemCategory::LEVEL_SUB_CATEGORY) {
            $data['category_id'] = $category->parent_id;
            $data['domain_id'] = $category->parent?->parent_id;
        }

        return $data;
    }

}
