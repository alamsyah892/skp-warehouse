<?php

use App\Models\PurchaseOrder;
use Tests\TestCase;

uses(TestCase::class);

it('treats blank form state as zero for watched decimal fields', function () {
    $purchaseOrder = new PurchaseOrder();
    $purchaseOrder->forceFill([
        'description' => 'PO existing',
        'vendor_id' => 1,
        'warehouse_address_id' => 2,
        'delivery_date' => '2026-04-26',
        'shipping_method' => 'Truck',
        'delivery_notes' => 'Notes',
        'terms' => 'Net 30',
        'tax_type' => 'exclude',
        'tax_percentage' => 11,
        'tax_description' => 'VAT',
        'discount' => 0,
        'rounding' => 0,
    ]);
    $purchaseOrder->syncOriginal();

    expect($purchaseOrder->hasWatchedFieldChangesFromState([
        'discount' => '',
        'rounding' => '',
    ]))->toBeFalse();
});
