<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;

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

    protected function pdfView(PurchaseOrder $purchaseOrder): string
    {
        return 'pdf.purchase-order-ko-'.$purchaseOrder->type->viewSuffix();
    }

    protected function filenamePrefix(): string
    {
        return 'KO_';
    }
}
