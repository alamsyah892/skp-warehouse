<?php

use App\Models\PurchaseRequestItem;

it('returns empty selectable options when purchase request ids are empty', function () {
    expect(PurchaseRequestItem::getOptions([]))->toBe([]);
});

it('returns null when resolving selectable record without id', function () {
    expect(PurchaseRequestItem::findWithDetail(null))->toBeNull();
});

