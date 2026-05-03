<?php

use App\Models\PurchaseOrderItem;

it('returns empty selectable options when purchase order id is invalid', function () {
    expect(PurchaseOrderItem::getOptions(0))->toBe([]);
});

it('returns null when resolving selectable record without id', function () {
    expect(PurchaseOrderItem::findWithDetail(null))->toBeNull();
});
