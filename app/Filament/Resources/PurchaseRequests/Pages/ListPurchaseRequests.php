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
            __('purchase-request.status.all') => Tab::make()
                ->icon(Heroicon::Bars4)
            ,
            PurchaseRequest::getStatusLabel(PurchaseRequest::STATUS_DRAFT) => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_DRAFT))
                ->icon(PurchaseRequest::getStatusIcon(PurchaseRequest::STATUS_DRAFT))
                ->badge($getStatusBadge(PurchaseRequest::STATUS_DRAFT))
            ,
            PurchaseRequest::getStatusLabel(PurchaseRequest::STATUS_CANCELED) => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_CANCELED))
                ->icon(PurchaseRequest::getStatusIcon(PurchaseRequest::STATUS_CANCELED))
            // ->badge($getStatusBadge(PurchaseRequest::STATUS_CANCELED))
            ,
            PurchaseRequest::getStatusLabel(PurchaseRequest::STATUS_REQUESTED) => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_REQUESTED))
                ->icon(PurchaseRequest::getStatusIcon(PurchaseRequest::STATUS_REQUESTED))
                ->badge($getStatusBadge(PurchaseRequest::STATUS_REQUESTED))
            ,
            PurchaseRequest::getStatusLabel(PurchaseRequest::STATUS_APPROVED) => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_APPROVED))
                ->icon(PurchaseRequest::getStatusIcon(PurchaseRequest::STATUS_APPROVED))
                ->badge($getStatusBadge(PurchaseRequest::STATUS_APPROVED))
            ,
            PurchaseRequest::getStatusLabel(PurchaseRequest::STATUS_ORDERED) => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_ORDERED))
                ->icon(PurchaseRequest::getStatusIcon(PurchaseRequest::STATUS_ORDERED))
                ->badge($getStatusBadge(PurchaseRequest::STATUS_ORDERED))
            ,
            PurchaseRequest::getStatusLabel(PurchaseRequest::STATUS_FINISHED) => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequest::STATUS_FINISHED))
                ->icon(PurchaseRequest::getStatusIcon(PurchaseRequest::STATUS_FINISHED))
            // ->badge($getStatusBadge(PurchaseRequest::STATUS_FINISHED))
            ,
        ];
    }
}
