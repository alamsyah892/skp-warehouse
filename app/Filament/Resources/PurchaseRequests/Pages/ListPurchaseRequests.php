<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Enums\PurchaseRequestStatus;
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
            PurchaseRequestStatus::DRAFT->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequestStatus::DRAFT))
                ->icon(PurchaseRequestStatus::DRAFT->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::DRAFT))
            ,
            PurchaseRequestStatus::CANCELED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequestStatus::CANCELED))
                ->icon(PurchaseRequestStatus::CANCELED->icon())
            // ->badge($getStatusBadge(PurchaseRequestStatus::CANCELED))
            ,
            PurchaseRequestStatus::REQUESTED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequestStatus::REQUESTED))
                ->icon(PurchaseRequestStatus::REQUESTED->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::REQUESTED))
            ,
            PurchaseRequestStatus::APPROVED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequestStatus::APPROVED))
                ->icon(PurchaseRequestStatus::APPROVED->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::APPROVED))
            ,
            PurchaseRequestStatus::ORDERED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequestStatus::ORDERED))
                ->icon(PurchaseRequestStatus::ORDERED->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::ORDERED))
            ,
            PurchaseRequestStatus::FINISHED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequestStatus::FINISHED))
                ->icon(PurchaseRequestStatus::FINISHED->icon())
            // ->badge($getStatusBadge(PurchaseRequestStatus::FINISHED))
            ,
        ];
    }
}
