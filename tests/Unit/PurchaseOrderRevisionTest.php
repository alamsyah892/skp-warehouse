<?php

use App\Models\PurchaseOrder;
use Tests\TestCase;

uses(TestCase::class);

it('does not require revision info when zero-equivalent decimal fields are undone to blank state', function () {
    $purchaseOrder = new PurchaseOrder();
    $purchaseOrder->forceFill([
        'description' => 'Existing purchase order',
        'vendor_id' => 10,
        'warehouse_address_id' => 20,
        'delivery_date' => '2026-04-26',
        'shipping_method' => 'Truck',
        'delivery_notes' => 'Handle with care',
        'terms' => 'Net 30',
        'tax_type' => 'exclude',
        'tax_percentage' => 11,
        'tax_description' => 'VAT',
        'discount' => '0.00',
        'rounding' => '0.00',
    ]);
    $purchaseOrder->syncOriginal();

    expect($purchaseOrder->hasWatchedFieldChangesFromState([
        'discount' => '',
        'rounding' => '',
    ]))->toBeFalse();
});
