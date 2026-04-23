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
        $counts = PurchaseRequest::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $getStatusBadge = fn (PurchaseRequestStatus $status) => (int) ($counts[$status->value] ?? 0) ?: null;

        return [
            __('purchase-request.status.all') => Tab::make()
                ->icon(Heroicon::Bars4)
            ,
            PurchaseRequestStatus::DRAFT->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequestStatus::DRAFT))
                ->icon(PurchaseRequestStatus::DRAFT->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::DRAFT))
                ->badgeColor(PurchaseRequestStatus::DRAFT->color())
            ,
            PurchaseRequestStatus::CANCELED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequestStatus::CANCELED))
                ->icon(PurchaseRequestStatus::CANCELED->icon())
            ,
            PurchaseRequestStatus::REQUESTED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequestStatus::REQUESTED))
                ->icon(PurchaseRequestStatus::REQUESTED->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::REQUESTED))
                ->badgeColor(PurchaseRequestStatus::REQUESTED->color())
            ,
            PurchaseRequestStatus::APPROVED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequestStatus::APPROVED))
                ->icon(PurchaseRequestStatus::APPROVED->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::APPROVED))
                ->badgeColor(PurchaseRequestStatus::APPROVED->color())
            ,
            PurchaseRequestStatus::ORDERED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequestStatus::ORDERED))
                ->icon(PurchaseRequestStatus::ORDERED->icon())
                ->badge($getStatusBadge(PurchaseRequestStatus::ORDERED))
                ->badgeColor(PurchaseRequestStatus::ORDERED->color())
            ,
            PurchaseRequestStatus::FINISHED->label() => Tab::make()
                ->modifyQueryUsing(fn($query) => $query->where('status', PurchaseRequestStatus::FINISHED))
                ->icon(PurchaseRequestStatus::FINISHED->icon())
            ,
        ];
    }
}
