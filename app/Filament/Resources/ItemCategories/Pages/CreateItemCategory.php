<?php

namespace App\Filament\Resources\ItemCategories\Pages;

use App\Filament\Resources\ItemCategories\ItemCategoryResource;
use App\Models\ItemCategory;
use Filament\Resources\Pages\CreateRecord;

class CreateItemCategory extends CreateRecord
{
    protected static string $resource = ItemCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['parent_id'] = match ((int) $data['level']) {
            ItemCategory::LEVEL_DOMAIN => null,
            ItemCategory::LEVEL_CATEGORY => $data['domain_id'],
            ItemCategory::LEVEL_SUB_CATEGORY => $data['category_id'],
        // ItemCategory::LEVEL_FINAL_CATEGORY => $data['sub_category_id'],
        };

        return $data;
    }

}
