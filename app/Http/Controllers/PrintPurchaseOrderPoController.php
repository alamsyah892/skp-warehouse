<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;

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

    protected function pdfView(PurchaseOrder $purchaseOrder): string
    {
        return 'pdf.purchase-order-'.$purchaseOrder->type->viewSuffix();
    }

    protected function filenamePrefix(): string
    {
        return 'PO_';
    }
}
