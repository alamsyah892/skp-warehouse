<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPurchaseRequest extends ViewRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print PR')
                ->icon(Heroicon::OutlinedPrinter)
                ->button()
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('print', $this->record) ?? false)
                ->url(fn (): string => route('purchase-requests.print', ['purchaseRequest' => $this->record]))
                ->openUrlInNewTab(),
            EditAction::make()
                ->icon(Heroicon::PencilSquare)
                ->button(),
        ];
    }
}
