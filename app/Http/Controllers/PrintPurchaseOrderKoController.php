<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;

class PrintPurchaseOrderKoController extends PurchaseOrderPrintController
{
    protected function printableStatus(): PurchaseOrderStatus
    {
        return PurchaseOrderStatus::DRAFT;
    }

    protected function printAbility(): string
    {
        return 'printKo';
    }

    protected function pdfView(): string
    {
        return 'pdf.purchase-order-ko';
    }

    protected function filenamePrefix(): string
    {
        return 'KO_';
    }
}
