<?php

use App\Filament\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Models\PurchaseOrderItem;

it('returns a neutral received quantity color for a new purchase order item row', function () {
    expect(PurchaseOrderForm::getReceivedQtyColumnColor(null))->toBe('gray');
});

it('returns a danger received quantity color when nothing has been received', function () {
    $purchaseOrderItem = new PurchaseOrderItem([
        'qty' => 10,
    ]);

    expect(PurchaseOrderForm::getReceivedQtyColumnColor($purchaseOrderItem))->toBe('danger');
});
