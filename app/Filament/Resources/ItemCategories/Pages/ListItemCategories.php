<?php

namespace App\Filament\Resources\ItemCategories\Pages;

use App\Filament\Resources\ItemCategories\ItemCategoryResource;
use App\Models\ItemCategory;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListItemCategories extends ListRecords
{
    protected static string $resource = ItemCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::Plus)
                ->button()
            ,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'Domain' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('level', ItemCategory::LEVEL_DOMAIN))
            // ->badge(ItemCategory::query()->where('level', ItemCategory::LEVEL_DOMAIN)->count())
            ,
            'Category' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('level', ItemCategory::LEVEL_CATEGORY))
            // ->badge(ItemCategory::query()->where('level', ItemCategory::LEVEL_CATEGORY)->count())
            ,
            'Sub Category' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('level', ItemCategory::LEVEL_SUB_CATEGORY))
            // ->badge(ItemCategory::query()->where('level', ItemCategory::LEVEL_SUB_CATEGORY)->count())
            ,
            // 'Final Category' => Tab::make()
            //     ->modifyQueryUsing(fn(Builder $query) => $query->where('level', ItemCategory::LEVEL_FINAL_CATEGORY))
            //     ->badge(ItemCategory::query()->where('level', ItemCategory::LEVEL_FINAL_CATEGORY)->count())
            // ,
        ];
    }
}
