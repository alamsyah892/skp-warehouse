<?php

namespace App\Filament\Resources\GoodsReceives\Pages;

use App\Filament\Resources\GoodsReceives\GoodsReceiveResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewGoodsReceive extends ViewRecord
{
    protected static string $resource = GoodsReceiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print GR')
                ->icon(Heroicon::OutlinedPrinter)
                ->button()
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('print', $this->record) ?? false)
                ->url(fn (): string => route('goods-receives.print', ['goodsReceive' => $this->record]))
                ->openUrlInNewTab(),
            EditAction::make()
                ->icon(Heroicon::PencilSquare)
                ->button(),
        ];
    }
}
