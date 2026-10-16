<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;

class PrintPurchaseOrderPoController extends PurchaseOrderPrintController
{
    protected function printableStatus(): PurchaseOrderStatus
    {
        return PurchaseOrderStatus::ORDERED;
    }

    protected function printAbility(): string
    {
        return 'printPo';
    }

    protected function pdfView(): string
    {
        return 'pdf.purchase-order-red';
    }

    protected function filenamePrefix(): string
    {
        return 'PO_';
    }
}
