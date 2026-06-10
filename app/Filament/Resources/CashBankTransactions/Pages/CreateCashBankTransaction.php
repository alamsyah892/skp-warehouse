<?php

namespace App\Filament\Resources\CashBankTransactions\Pages;

use App\Filament\Resources\CashBankTransactions\CashBankTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCashBankTransaction extends CreateRecord
{
    protected static string $resource = CashBankTransactionResource::class;
}
