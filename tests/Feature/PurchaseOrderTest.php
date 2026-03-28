<?php

use App\Models\PurchaseOrder;

it('normalizes selected purchase request ids', function () {
    expect(PurchaseOrder::normalizePurchaseRequestIds([1, '2', null, '2', 0, [3, '4']]))
        ->toBe([1, 2, 3, 4]);
});

it('calculates item total with discount', function () {
    expect(PurchaseOrder::calculateItemTotal([
        'qty' => 5,
        'price' => 10000,
        'discount' => 12500,
    ]))->toBe(37500.0);
});

it('does not allow negative item total', function () {
    expect(PurchaseOrder::calculateItemTotal([
        'qty' => 1,
        'price' => 5000,
        'discount' => 6000,
    ]))->toBe(0.0);
});

it('calculates subtotal, net subtotal, and grand total', function () {
    $items = [
        [
            'qty' => 2,
            'price' => 10000,
            'discount' => 1000,
        ],
        [
            'qty' => 3,
            'price' => 5000,
            'discount' => 500,
        ],
    ];

    expect(PurchaseOrder::calculateSubtotal($items))->toBe(33500.0);
    expect(PurchaseOrder::calculateNetSubtotal($items, 3500))->toBe(30000.0);
    expect(PurchaseOrder::calculateGrandTotal($items, 3500, 3300, 200))->toBe(33500.0);
});
