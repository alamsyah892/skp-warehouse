<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListPurchaseRequests extends ListRecords
{
    protected static string $resource = PurchaseRequestResource::class;

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
            'all' => Tab::make()
                ->icon(Heroicon::Bars4)
            ,
            'Draft' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_DRAFT))
                ->icon(PurchaseRequest::STATUS_ICONS[PurchaseRequest::STATUS_DRAFT])
            // ->badge(PurchaseRequest::query()->where('status', PurchaseRequest::STATUS_DRAFT)->count())
            ,
            'Canceled' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_CANCELED))
                ->icon(PurchaseRequest::STATUS_ICONS[PurchaseRequest::STATUS_CANCELED])
            // ->badge(PurchaseRequest::query()->where('status', PurchaseRequest::STATUS_CANCELED)->count())
            ,
            'Waiting' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_WAITING))
                ->icon(PurchaseRequest::STATUS_ICONS[PurchaseRequest::STATUS_WAITING])
            // ->badge(PurchaseRequest::query()->where('status', PurchaseRequest::STATUS_WAITING)->count())
            ,
            'Received' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_RECEIVED))
                ->icon(PurchaseRequest::STATUS_ICONS[PurchaseRequest::STATUS_RECEIVED])
            // ->badge(PurchaseRequest::query()->where('status', PurchaseRequest::STATUS_RECEIVED)->count())
            ,
            'Ordered' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_ORDERED))
                ->icon(PurchaseRequest::STATUS_ICONS[PurchaseRequest::STATUS_ORDERED])
            // ->badge(PurchaseRequest::query()->where('status', PurchaseRequest::STATUS_ORDERED)->count())
            ,
            'Finish' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_FINISH))
                ->icon(PurchaseRequest::STATUS_ICONS[PurchaseRequest::STATUS_FINISH])
            // ->badge(PurchaseRequest::query()->where('status', PurchaseRequest::STATUS_FINISH)->count())
            ,
        ];
    }
}
