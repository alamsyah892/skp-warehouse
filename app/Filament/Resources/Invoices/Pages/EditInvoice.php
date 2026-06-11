<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\GoodsReceive;
use App\Models\Invoice;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->prepareInvoiceData($data);
    }

    private function prepareInvoiceData(array $data): array
    {
        $goodsReceive = GoodsReceive::query()->find($data['goods_receive_id'] ?? null);
        $data['purchase_order_id'] = $goodsReceive?->purchase_order_id ?? $data['purchase_order_id'] ?? null;
        $data['total_amount'] = Invoice::calculateSummary(
            (array) ($data['invoiceItems'] ?? []),
            (float) ($data['discount'] ?? 0),
            $data['tax_type'] ?? null,
            $data['tax_percentage'] ?? 0,
            (float) ($data['rounding'] ?? 0),
        )['grand_total'];

        return $data;
    }
}
