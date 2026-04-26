<?php

use App\Filament\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Models\PurchaseOrderItem;

it('returns a danger received quantity color for an empty purchase order item row', function () {
    $purchaseOrderItem = new PurchaseOrderItem([
        'qty' => 0,
    ]);

    expect($purchaseOrderItem->getReceivedQtyColor())->toBe('danger');
});

it('returns a danger received quantity color when nothing has been received', function () {
    $purchaseOrderItem = new PurchaseOrderItem([
        'qty' => 10,
    ]);

    expect($purchaseOrderItem->getReceivedQtyColor())->toBe('danger');
});

it('disables item selection when source purchase request item is selected', function () {
    expect(PurchaseOrderForm::isItemSelectionDisabled(null, 123))->toBeTrue();
});

it('keeps item selection enabled when no source purchase request item is selected', function () {
    expect(PurchaseOrderForm::isItemSelectionDisabled(null, null))->toBeFalse();
});
