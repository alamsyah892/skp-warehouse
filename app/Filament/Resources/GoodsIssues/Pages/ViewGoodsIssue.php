<?php

namespace App\Filament\Resources\GoodsIssues\Pages;

use App\Filament\Resources\GoodsIssues\GoodsIssueResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewGoodsIssue extends ViewRecord
{
    protected static string $resource = GoodsIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print GI')
                ->icon(Heroicon::OutlinedPrinter)
                ->button()
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('print', $this->record) ?? false)
                ->url(fn (): string => route('goods-issues.print', ['goodsIssue' => $this->record]))
                ->openUrlInNewTab(),
            EditAction::make()
                ->icon(Heroicon::PencilSquare)
                ->button(),
        ];
    }
}
