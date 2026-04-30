<?php

namespace App\Filament\Resources\GoodsIssues\Pages;

use App\Enums\GoodsIssueStatus;
use App\Filament\Resources\GoodsIssues\GoodsIssueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListGoodsIssues extends ListRecords
{
    protected static string $resource = GoodsIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::Plus)
                ->button(),
        ];
    }

    public function getTabs(): array
    {
        return [
            __('goods-issue.status.all') => Tab::make()->icon(Heroicon::Bars4),
            GoodsIssueStatus::ISSUED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', GoodsIssueStatus::ISSUED))
                ->icon(GoodsIssueStatus::ISSUED->icon()),
            GoodsIssueStatus::CANCELED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', GoodsIssueStatus::CANCELED))
                ->icon(GoodsIssueStatus::CANCELED->icon()),
        ];
    }
}
