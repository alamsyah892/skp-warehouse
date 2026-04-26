<?php

use App\Enums\PurchaseOrderTaxType;
use App\Models\PurchaseOrder;

it('calculates summary totals without per-item breakdown', function () {
    $summary = PurchaseOrder::calculateOrderSummary(
        items: [
            ['qty' => 2, 'price' => 100_000],
            ['qty' => 1, 'price' => 50_000],
        ],
        orderDiscount: 10_000,
        taxType: PurchaseOrderTaxType::EXCLUDE,
        taxPercentage: 11,
        rounding: -500,
    );

    expect($summary)->toMatchArray([
        'subtotal' => 250_000.0,
        'discount' => 10_000.0,
        'subtotal_after_discount' => 240_000.0,
        'tax_base' => 240_000.0,
        'tax_amount' => 26_400.0,
        'total_before_rounding' => 266_400.0,
        'rounding' => -500.0,
        'grand_total' => 265_900.0,
    ]);
});

it('ignores non pricing fields and only uses qty and price for summary', function () {
    $summary = PurchaseOrder::calculateOrderSummary(
        items: [
            ['id' => 1, 'qty' => 3, 'price' => 80_000, 'discount' => 99_000, 'tax_amount' => 999],
        ],
        orderDiscount: 5_000,
        taxType: PurchaseOrderTaxType::INCLUDE,
        taxPercentage: 11,
        rounding: 250,
    );

    expect($summary)->toMatchArray([
        'subtotal' => 240_000.0,
        'discount' => 5_000.0,
        'subtotal_after_discount' => 235_000.0,
        'tax_base' => 211_712.0,
        'tax_amount' => 23_288.0,
        'total_before_rounding' => 235_000.0,
        'rounding' => 250.0,
        'grand_total' => 235_250.0,
    ]);
});
