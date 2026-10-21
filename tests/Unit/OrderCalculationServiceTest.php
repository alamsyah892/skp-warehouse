<?php

use App\Enums\PurchaseOrderTaxType;
use App\Services\OrderCalculationService;

test('normalizes summary items from arrays and collections', function (): void {
    $items = [
        ['qty' => 2, 'price' => 500],
        ['qty' => '3', 'price' => '1000'],
        ['qty' => -1, 'price' => 200], // negative qty should become 0
        ['qty' => 1, 'price' => -50],  // negative price should become 0
        ['invalid' => 'format'],
        'non-array',
    ];

    $normalized = OrderCalculationService::normalizeSummaryItems($items);

    expect($normalized)->toBe([
        ['qty' => 2.0, 'price' => 500.0],
        ['qty' => 3.0, 'price' => 1000.0],
        ['qty' => 0.0, 'price' => 200.0],
        ['qty' => 1.0, 'price' => 0.0],
        ['qty' => 0.0, 'price' => 0.0],
    ]);
});

test('normalizes summary items from objects', function (): void {
    $items = collect([
        (object) ['qty' => 4, 'price' => 250],
        (object) ['qty' => 2, 'price' => 100],
    ]);

    $normalized = OrderCalculationService::normalizeSummaryItems($items);

    expect($normalized)->toBe([
        ['qty' => 4.0, 'price' => 250.0],
        ['qty' => 2.0, 'price' => 100.0],
    ]);
});

test('calculates subtotal correctly', function (): void {
    $items = [
        ['qty' => 2, 'price' => 1500],
        ['qty' => 3, 'price' => 500],
    ];

    expect(OrderCalculationService::calculateSubtotal($items))->toBe(4500.0);
});

test('provides standard tax percentage options', function (): void {
    $options = OrderCalculationService::getTaxPercentageOptions();

    expect($options)->toMatchArray([
        0 => '0%',
        10 => '10%',
        11 => '11%',
        12 => '12%',
    ]);
});

test('normalizes tax types from enums and strings', function (): void {
    expect(OrderCalculationService::normalizeTaxType(PurchaseOrderTaxType::INCLUDE))->toBe(PurchaseOrderTaxType::INCLUDE)
        ->and(OrderCalculationService::normalizeTaxType('include'))->toBe(PurchaseOrderTaxType::INCLUDE)
        ->and(OrderCalculationService::normalizeTaxType('exclude'))->toBe(PurchaseOrderTaxType::EXCLUDE)
        ->and(OrderCalculationService::normalizeTaxType(null))->toBeNull()
        ->and(OrderCalculationService::normalizeTaxType('invalid'))->toBeNull();
});

test('calculates exclude tax correctly', function (): void {
    $tax = OrderCalculationService::calculateTaxAmount(
        netSubtotal: 10000,
        taxType: PurchaseOrderTaxType::EXCLUDE,
        taxPercentage: 11,
    );

    expect($tax)->toBe(1100.0);
});

test('calculates include tax correctly', function (): void {
    $tax = OrderCalculationService::calculateTaxAmount(
        netSubtotal: 11100,
        taxType: PurchaseOrderTaxType::INCLUDE,
        taxPercentage: 11,
    );

    expect(round($tax, 2))->toBe(1100.0);
});

test('calculates tax as zero when tax type is null or percentage is zero', function (): void {
    expect(OrderCalculationService::calculateTaxAmount(10000, null, 11))->toBe(0.0)
        ->and(OrderCalculationService::calculateTaxAmount(10000, PurchaseOrderTaxType::EXCLUDE, 0))->toBe(0.0)
        ->and(OrderCalculationService::calculateTaxAmount(0, PurchaseOrderTaxType::EXCLUDE, 11))->toBe(0.0);
});

test('calculates order summary with exclude tax, discount, and rounding', function (): void {
    $items = [
        ['qty' => 2, 'price' => 1000],
        ['qty' => 3, 'price' => 500],
    ];

    $summary = OrderCalculationService::calculateSummary(
        items: $items,
        discount: 500,
        taxType: PurchaseOrderTaxType::EXCLUDE,
        taxPercentage: 11,
        rounding: 5,
    );

    expect($summary)->toMatchArray([
        'subtotal' => 3500.0,
        'discount' => 500.0,
        'subtotal_after_discount' => 3000.0,
        'tax_base' => 3000.0,
        'tax_amount' => 330.0,
        'tax' => 330.0,
        'total_before_rounding' => 3330.0,
        'rounding' => 5.0,
        'grand_total' => 3335.0,
    ]);
});

test('calculates order summary with include tax', function (): void {
    $items = [
        ['qty' => 1, 'price' => 11100],
    ];

    $summary = OrderCalculationService::calculateSummary(
        items: $items,
        discount: 0,
        taxType: PurchaseOrderTaxType::INCLUDE,
        taxPercentage: 11,
        rounding: 0,
    );

    expect($summary['subtotal'])->toBe(11100.0)
        ->and($summary['discount'])->toBe(0.0)
        ->and($summary['subtotal_after_discount'])->toBe(11100.0)
        ->and(round($summary['tax_amount'], 2))->toBe(1100.0)
        ->and(round($summary['tax_base'], 2))->toBe(10000.0)
        ->and($summary['total_before_rounding'])->toBe(11100.0)
        ->and($summary['grand_total'])->toBe(11100.0);
});

test('discount cannot exceed subtotal', function (): void {
    $items = [
        ['qty' => 1, 'price' => 1000],
    ];

    $summary = OrderCalculationService::calculateSummary(
        items: $items,
        discount: 1500,
        taxType: null,
        taxPercentage: null,
        rounding: 0,
    );

    expect($summary['subtotal'])->toBe(1000.0)
        ->and($summary['discount'])->toBe(1000.0)
        ->and($summary['subtotal_after_discount'])->toBe(0.0)
        ->and($summary['grand_total'])->toBe(0.0);
});

test('calculateOrderSummary alias returns identical result to calculateSummary', function (): void {
    $items = [
        ['qty' => 2, 'price' => 1000],
        ['qty' => 3, 'price' => 500],
    ];

    $summary = OrderCalculationService::calculateOrderSummary(
        items: $items,
        discount: 500,
        taxType: PurchaseOrderTaxType::EXCLUDE,
        taxPercentage: 11,
        rounding: 5,
    );

    expect($summary)->toMatchArray([
        'subtotal' => 3500.0,
        'discount' => 500.0,
        'subtotal_after_discount' => 3000.0,
        'tax_base' => 3000.0,
        'tax_amount' => 330.0,
        'tax' => 330.0,
        'total_before_rounding' => 3330.0,
        'rounding' => 5.0,
        'grand_total' => 3335.0,
    ]);
});

test('service instance summary method works for dependency injection', function (): void {
    $service = new OrderCalculationService;
    $items = [
        ['qty' => 1, 'price' => 1000],
    ];

    $summary = $service->summary($items);

    expect($summary['subtotal'])->toBe(1000.0)
        ->and($summary['grand_total'])->toBe(1000.0);
});
