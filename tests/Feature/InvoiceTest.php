<?php

use App\Enums\PurchaseOrderTaxType;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\GoodsReceive;
use App\Models\GoodsReceiveItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('invoice resource is configured correctly', function (): void {
    expect(InvoiceResource::getModel())
        ->toBe(Invoice::class)
        ->and(InvoiceResource::getNavigationGroup())
        ->toBe('Finance')
        ->and(InvoiceResource::getNavigationLabel())
        ->toBe('Invoice')
        ->and(InvoiceResource::getGloballySearchableAttributes())
        ->toBe(['number', 'vendor.name', 'goodsReceive.number', 'purchaseOrder.number']);
});

test('invoice summary follows purchase order tax discount and rounding rules', function (): void {
    $summary = Invoice::calculateSummary(
        [
            ['qty' => 2, 'price' => 1000],
            ['qty' => 3, 'price' => 500],
        ],
        discount: 500,
        taxType: PurchaseOrderTaxType::EXCLUDE,
        taxPercentage: 11,
        rounding: 5,
    );

    expect($summary['subtotal'])->toBe(3500.0)
        ->and($summary['discount'])->toBe(500.0)
        ->and($summary['subtotal_after_discount'])->toBe(3000.0)
        ->and($summary['tax'])->toBe(330.0)
        ->and($summary['grand_total'])->toBe(3335.0);
});

test('invoice stores goods receive item price subtotal and total amount', function (): void {
    $vendor = Vendor::factory()->create();
    $item = Item::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()
        ->for($vendor)
        ->create([
            'discount' => 500,
            'tax_type' => PurchaseOrderTaxType::EXCLUDE,
            'tax_percentage' => 11,
            'rounding' => 5,
        ]);
    $purchaseOrderItem = PurchaseOrderItem::factory()
        ->for($purchaseOrder)
        ->for($item)
        ->create([
            'qty' => 5,
            'price' => 1000,
        ]);
    $goodsReceive = GoodsReceive::factory()
        ->for($purchaseOrder)
        ->create([
            'company_id' => $purchaseOrder->company_id,
            'warehouse_id' => $purchaseOrder->warehouse_id,
            'warehouse_address_id' => $purchaseOrder->warehouse_address_id,
            'division_id' => $purchaseOrder->division_id,
            'project_id' => $purchaseOrder->project_id,
        ]);
    $goodsReceiveItem = GoodsReceiveItem::factory()
        ->for($goodsReceive)
        ->for($purchaseOrderItem)
        ->for($item)
        ->create([
            'qty' => 3,
        ]);

    $summary = Invoice::calculateSummary(
        [
            ['qty' => $goodsReceiveItem->qty, 'price' => $purchaseOrderItem->price],
        ],
        discount: 500,
        taxType: PurchaseOrderTaxType::EXCLUDE,
        taxPercentage: 11,
        rounding: 5,
    );

    $invoice = Invoice::factory()
        ->for($vendor)
        ->for($goodsReceive, 'goodsReceive')
        ->for($purchaseOrder, 'purchaseOrder')
        ->create([
            'discount' => 500,
            'tax_type' => PurchaseOrderTaxType::EXCLUDE,
            'tax_percentage' => 11,
            'rounding' => 5,
            'total_amount' => $summary['grand_total'],
        ]);
    $invoiceItem = InvoiceItem::factory()
        ->for($invoice)
        ->for($goodsReceiveItem, 'goodsReceiveItem')
        ->for($purchaseOrderItem, 'purchaseOrderItem')
        ->for($item)
        ->create([
            'qty' => $goodsReceiveItem->qty,
            'price' => $purchaseOrderItem->price,
        ]);

    $invoice->refresh();
    $invoiceItem->refresh();

    expect($invoice->vendor->is($vendor))->toBeTrue()
        ->and($invoice->goodsReceive->is($goodsReceive))->toBeTrue()
        ->and($invoice->purchaseOrder->is($purchaseOrder))->toBeTrue()
        ->and($invoice->total_amount)->toBe('2780.00')
        ->and($invoiceItem->subtotal)->toBe('3000.00');
});
