<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printKo')
                ->label('Print KO')
                ->icon(Heroicon::OutlinedPrinter)
                ->button()
                ->color('gray')
                ->visible(fn (): bool => $this->record->status === PurchaseOrderStatus::DRAFT
                    && auth()->user()?->can('printKo', $this->record))
                ->url(fn (): string => route('purchase-orders.print-ko', ['purchaseOrder' => $this->record]))
                ->openUrlInNewTab(),
            Action::make('printPo')
                ->label('Print PO')
                ->icon(Heroicon::OutlinedPrinter)
                ->button()
                ->color('gray')
                ->visible(fn (): bool => $this->record->status === PurchaseOrderStatus::ORDERED
                    && auth()->user()?->can('printPo', $this->record))
                ->url(fn (): string => route('purchase-orders.print-po', ['purchaseOrder' => $this->record]))
                ->openUrlInNewTab(),
            EditAction::make()
                ->icon(Heroicon::PencilSquare)
                ->button(),
        ];
    }
}
