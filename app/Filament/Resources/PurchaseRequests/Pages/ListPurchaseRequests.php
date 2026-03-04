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
        $getStatusBadge = function ($status) {
            $count = PurchaseRequest::where('status', $status)->count();
            return $count > 0 ? $count : null;
        };

        return [
            'all' => Tab::make()
                ->icon(Heroicon::Bars4)
            ,
            'Draft' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_DRAFT))
                ->icon(PurchaseRequest::STATUS_ICONS[PurchaseRequest::STATUS_DRAFT])
                ->badge($getStatusBadge(PurchaseRequest::STATUS_DRAFT))
            ,
            'Canceled' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_CANCELED))
                ->icon(PurchaseRequest::STATUS_ICONS[PurchaseRequest::STATUS_CANCELED])
            // ->badge($getStatusBadge(PurchaseRequest::STATUS_CANCELED))
            ,
            'Waiting' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_WAITING))
                ->icon(PurchaseRequest::STATUS_ICONS[PurchaseRequest::STATUS_WAITING])
                ->badge($getStatusBadge(PurchaseRequest::STATUS_WAITING))
            ,
            'Received' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_RECEIVED))
                ->icon(PurchaseRequest::STATUS_ICONS[PurchaseRequest::STATUS_RECEIVED])
                ->badge($getStatusBadge(PurchaseRequest::STATUS_RECEIVED))
            ,
            'Ordered' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_ORDERED))
                ->icon(PurchaseRequest::STATUS_ICONS[PurchaseRequest::STATUS_ORDERED])
                ->badge($getStatusBadge(PurchaseRequest::STATUS_ORDERED))
            ,
            'Finish' => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_FINISH))
                ->icon(PurchaseRequest::STATUS_ICONS[PurchaseRequest::STATUS_FINISH])
            // ->badge($getStatusBadge(PurchaseRequest::STATUS_FINISH))
            ,
        ];
    }
}
