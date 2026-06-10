<?php

namespace App\Filament\Resources\CashBankTransactions\Pages;

use App\Filament\Resources\CashBankTransactions\CashBankTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCashBankTransaction extends ViewRecord
{
    protected static string $resource = CashBankTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
