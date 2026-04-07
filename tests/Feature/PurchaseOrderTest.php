<?php

use App\Enums\PurchaseOrderTaxType;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;

uses(DatabaseTransactions::class);

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
    expect(PurchaseOrder::calculateTotalSubtotal($items, PurchaseOrderTaxType::EXCLUDE, 11))->toBe(35000.0);
    expect(PurchaseOrder::calculateNetSubtotal($items, 3500))->toBe(30000.0);
    expect(PurchaseOrder::calculateSubtotalTax($items, 3500, PurchaseOrderTaxType::EXCLUDE, 11))->toBe(3300.0);
    expect(PurchaseOrder::calculateGrandTotal($items, 3500, PurchaseOrderTaxType::EXCLUDE, 11, 200))->toBe(33500.0);
});

it('extracts tax amount from include tax purchase order', function () {
    $items = [
        [
            'qty' => 1,
            'price' => 111000,
            'discount' => 0,
        ],
    ];

    expect(PurchaseOrder::calculateTotalSubtotal($items, PurchaseOrderTaxType::INCLUDE, 11))->toBe(100000.0);
    expect(PurchaseOrder::calculateSubtotalTax($items, 0, PurchaseOrderTaxType::INCLUDE, 11))->toBe(11000.0);
    expect(PurchaseOrder::calculateGrandTotal($items, 0, PurchaseOrderTaxType::INCLUDE, 11, 0))->toBe(111000.0);
});

it('keeps include tax breakdown consistent when discounts are applied', function () {
    $items = [
        [
            'qty' => 1,
            'price' => 111000,
            'discount' => 11100,
        ],
    ];

    $orderDiscount = 11100;

    $subtotal = PurchaseOrder::calculateTotalSubtotal($items, PurchaseOrderTaxType::INCLUDE, 11);
    $discount = PurchaseOrder::calculateSubtotalDiscount($items, $orderDiscount, PurchaseOrderTaxType::INCLUDE, 11);
    $tax = PurchaseOrder::calculateSubtotalTax($items, $orderDiscount, PurchaseOrderTaxType::INCLUDE, 11);
    $total = PurchaseOrder::calculateTotalBeforeRounding($items, $orderDiscount, PurchaseOrderTaxType::INCLUDE, 11);

    expect($subtotal)->toBe(100000.0);
    expect($discount)->toBe(20000.0);
    expect($tax)->toBe(8800.0);
    expect($total)->toBe(88800.0);
    expect(round($subtotal - $discount + $tax, 2))->toBe($total);
});

it('rounds tax and include dpp calculations', function () {
    $items = [
        [
            'qty' => 1,
            'price' => 100001,
            'discount' => 0,
        ],
    ];

    expect(PurchaseOrder::calculateTotalSubtotal($items, PurchaseOrderTaxType::INCLUDE, 11))->toBe(90091.0);
    expect(PurchaseOrder::calculateSubtotalTax($items, 0, PurchaseOrderTaxType::INCLUDE, 11))->toBe(9910.0);
    expect(PurchaseOrder::calculateSubtotalTax($items, 0, PurchaseOrderTaxType::EXCLUDE, 11))->toBe(11000.0);
});

it('rounds include dpp at document level like erp', function () {
    $items = [
        [
            'qty' => 1,
            'price' => 1,
            'discount' => 0,
        ],
        [
            'qty' => 1,
            'price' => 5,
            'discount' => 0,
        ],
    ];

    expect(PurchaseOrder::calculateTotalSubtotal($items, PurchaseOrderTaxType::INCLUDE, 11))->toBe(5.0);
});

it('allows manual purchase order items from categories that allow po', function () {
    $category = ItemCategory::query()->create([
        'level' => ItemCategory::LEVEL_SUB_CATEGORY,
        'code' => 'MISC',
        'name' => 'Miscellaneous',
        'description' => '',
        'allow_po' => true,
    ]);

    $item = Item::query()->create([
        'category_id' => $category->id,
        'code' => 'BIAYA-LAIN',
        'name' => 'Biaya Lain-lain',
        'description' => '',
        'unit' => 'lot',
        'type' => Item::TYPE_CONSUMABLE,
        'is_active' => true,
    ]);

    PurchaseOrder::validateManualItems([
        [
            'purchase_request_item_id' => null,
            'item_id' => $item->id,
            'qty' => 1,
            'price' => 100000,
            'discount' => 0,
        ],
    ]);

    expect(true)->toBeTrue();
});

it('rejects manual purchase order items from categories that do not allow po', function () {
    $category = ItemCategory::query()->create([
        'level' => ItemCategory::LEVEL_SUB_CATEGORY,
        'code' => 'NON-PO',
        'name' => 'Non PO',
        'description' => '',
        'allow_po' => false,
    ]);

    $item = Item::query()->create([
        'category_id' => $category->id,
        'code' => 'TIDAK-BOLEH',
        'name' => 'Tidak Boleh PO',
        'description' => '',
        'unit' => 'pcs',
        'type' => Item::TYPE_CONSUMABLE,
        'is_active' => true,
    ]);

    expect(fn() => PurchaseOrder::validateManualItems([
        [
            'purchase_request_item_id' => null,
            'item_id' => $item->id,
            'qty' => 1,
            'price' => 100000,
            'discount' => 0,
        ],
    ]))->toThrow(ValidationException::class);
});
