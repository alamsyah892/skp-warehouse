<?php

namespace App\Filament\Resources\CashBankTransactions\Pages;

use App\Filament\Resources\CashBankTransactions\CashBankTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCashBankTransaction extends EditRecord
{
    protected static string $resource = CashBankTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
