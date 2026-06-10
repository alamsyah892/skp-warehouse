<?php

namespace App\Filament\Resources\CashBankTransactions\Pages;

use App\Filament\Resources\CashBankTransactions\CashBankTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCashBankTransactions extends ListRecords
{
    protected static string $resource = CashBankTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::Plus)
                ->button(),
        ];
    }
}
