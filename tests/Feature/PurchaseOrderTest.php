<?php

use App\Enums\PurchaseOrderTaxType;
use App\Enums\PurchaseRequestStatus;
use App\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Models\Company;
use App\Models\Division;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Project;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

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

it('allocates order discount into each line breakdown consistently', function () {
    $items = [
        [
            'line_key' => 'line-1',
            'qty' => 1,
            'price' => 10000,
            'discount' => 0,
        ],
        [
            'line_key' => 'line-2',
            'qty' => 1,
            'price' => 5000,
            'discount' => 0,
        ],
    ];

    $breakdown = PurchaseOrder::calculateOrderBreakdown(
        $items,
        1500,
        PurchaseOrderTaxType::EXCLUDE,
        11,
        0,
    );

    expect($breakdown['lines']['line-1']['allocated_order_discount'])->toBe(1000.0)
        ->and($breakdown['lines']['line-1']['gross_after_discount'])->toBe(9000.0)
        ->and($breakdown['lines']['line-1']['tax_amount'])->toBe(990.0)
        ->and($breakdown['lines']['line-1']['total'])->toBe(9990.0)
        ->and($breakdown['lines']['line-2']['allocated_order_discount'])->toBe(500.0)
        ->and($breakdown['lines']['line-2']['gross_after_discount'])->toBe(4500.0)
        ->and($breakdown['lines']['line-2']['tax_amount'])->toBe(495.0)
        ->and($breakdown['lines']['line-2']['total'])->toBe(4995.0)
        ->and($breakdown['summary']['gross_subtotal'])->toBe(15000.0)
        ->and($breakdown['summary']['discount_total'])->toBe(1500.0)
        ->and($breakdown['summary']['gross_after_discount'])->toBe(13500.0)
        ->and($breakdown['summary']['tax_base'])->toBe(13500.0)
        ->and($breakdown['summary']['tax_amount'])->toBe(1485.0)
        ->and($breakdown['summary']['before_rounding'])->toBe(14985.0)
        ->and($breakdown['summary']['grand_total'])->toBe(14985.0);
});

it('uses indonesian effective ppn calculation for 12 percent exclude tax purchase orders', function () {
    $items = [
        [
            'qty' => 1,
            'price' => 100000,
            'discount' => 0,
        ],
    ];

    expect(PurchaseOrder::calculateSubtotalTax($items, 0, PurchaseOrderTaxType::EXCLUDE, 12))->toBe(11000.0);
    expect(PurchaseOrder::calculateGrandTotal($items, 0, PurchaseOrderTaxType::EXCLUDE, 12, 0))->toBe(111000.0);
});

it('uses indonesian effective ppn calculation for 12 percent include tax purchase orders', function () {
    $items = [
        [
            'qty' => 1,
            'price' => 111000,
            'discount' => 0,
        ],
    ];

    expect(PurchaseOrder::calculateTotalSubtotal($items, PurchaseOrderTaxType::INCLUDE, 12))->toBe(111000.0);
    expect(PurchaseOrder::calculateSubtotalTax($items, 0, PurchaseOrderTaxType::INCLUDE, 12))->toBe(11000.0);
    expect(PurchaseOrder::calculateGrandTotal($items, 0, PurchaseOrderTaxType::INCLUDE, 12, 0))->toBe(111000.0);
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

it('hydrates a stable line key for existing purchase order items on edit', function () {
    $purchaseOrder = createEditablePurchaseOrder();

    Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertFormSet(function (array $state): array {
            $firstPurchaseOrderItem = collect(data_get($state, 'purchaseOrderItems', []))->first();

            expect($firstPurchaseOrderItem['id'] ?? $firstPurchaseOrderItem['line_key'] ?? null)
                ->not->toBeEmpty();

            return [];
        });
});

it('preserves edited sourced item discounts and recalculates totals on edit save', function () {
    $purchaseOrder = createEditablePurchaseOrder();
    $updatedQty = 3;
    $updatedPrice = 15000;
    $updatedDiscount = 1200;
    $updatedRounding = 50;
    $firstPurchaseOrderItemKey = null;

    Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertFormSet(function (array $state) use (&$firstPurchaseOrderItemKey): array {
            $firstPurchaseOrderItemKey = array_key_first($state['purchaseOrderItems']);

            return [];
        })
        ->set("data.purchaseOrderItems.{$firstPurchaseOrderItemKey}.qty", $updatedQty)
        ->set("data.purchaseOrderItems.{$firstPurchaseOrderItemKey}.price", $updatedPrice)
        ->set("data.purchaseOrderItems.{$firstPurchaseOrderItemKey}.discount", $updatedDiscount)
        ->set('data.tax_type', PurchaseOrderTaxType::EXCLUDE->value)
        ->set('data.tax_percentage', (string) PurchaseOrder::DEFAULT_TAX_PERCENTAGE)
        ->set('data.rounding', $updatedRounding)
        ->call('save', false, false)
        ->assertHasNoFormErrors();

    $purchaseOrder->refresh();

    /** @var PurchaseOrderItem $purchaseOrderItem */
    $purchaseOrderItem = $purchaseOrder->purchaseOrderItems()->firstOrFail();

    $expectedTax = PurchaseOrder::calculateSubtotalTax(
        [[
            'line_key' => 'edited-line',
            'purchase_request_item_id' => $purchaseOrderItem->purchase_request_item_id,
            'qty' => $updatedQty,
            'price' => $updatedPrice,
            'discount' => $updatedDiscount,
        ]],
        0,
        PurchaseOrderTaxType::EXCLUDE,
        PurchaseOrder::DEFAULT_TAX_PERCENTAGE,
    );

    $expectedGrandTotal = PurchaseOrder::calculateGrandTotal(
        [[
            'line_key' => 'edited-line',
            'purchase_request_item_id' => $purchaseOrderItem->purchase_request_item_id,
            'qty' => $updatedQty,
            'price' => $updatedPrice,
            'discount' => $updatedDiscount,
        ]],
        0,
        PurchaseOrderTaxType::EXCLUDE,
        PurchaseOrder::DEFAULT_TAX_PERCENTAGE,
        $updatedRounding,
    );

    expect((float) $purchaseOrderItem->qty)->toBe((float) $updatedQty)
        ->and((float) $purchaseOrderItem->price)->toBe((float) $updatedPrice)
        ->and((float) $purchaseOrderItem->discount)->toBe((float) $updatedDiscount)
        ->and((float) $purchaseOrder->discount)->toBe(0.0)
        ->and((float) $purchaseOrder->tax)->toBe($expectedTax)
        ->and(PurchaseOrder::calculateGrandTotal(
            $purchaseOrder->purchaseOrderItems()
                ->get()
                ->map(fn(PurchaseOrderItem $item): array => [
                    'purchase_request_item_id' => $item->purchase_request_item_id,
                    'qty' => (float) $item->qty,
                    'price' => (float) $item->price,
                    'discount' => (float) $item->discount,
                ])
                ->all(),
            (float) $purchaseOrder->discount,
            $purchaseOrder->tax_type,
            (float) $purchaseOrder->tax_percentage,
            (float) $purchaseOrder->rounding,
        ))->toBe($expectedGrandTotal);
});

it('updates edit purchase order item state when values change', function () {
    $purchaseOrder = createEditablePurchaseOrder();
    $firstPurchaseOrderItemKey = null;

    Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertFormSet(function (array $state) use (&$firstPurchaseOrderItemKey): array {
            $firstPurchaseOrderItemKey = array_key_first($state['purchaseOrderItems']);

            expect($firstPurchaseOrderItemKey)->not->toBeNull();

            return [];
        })
        ->set("data.purchaseOrderItems.{$firstPurchaseOrderItemKey}.qty", 2)
        ->assertSet("data.purchaseOrderItems.{$firstPurchaseOrderItemKey}.qty", 2);
});

it('keeps finished purchase requests available on the edit page', function () {
    $purchaseOrder = createEditablePurchaseOrder(PurchaseRequestStatus::FINISHED);

    Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertFormSet(function (array $state) use ($purchaseOrder): array {
            expect(PurchaseOrder::normalizePurchaseRequestIds((array) ($state['purchaseRequests'] ?? [])))
                ->toContain($purchaseOrder->purchaseRequests->first()->id);

            return [];
        });
});

function createEditablePurchaseOrder(PurchaseRequestStatus $purchaseRequestStatus = PurchaseRequestStatus::APPROVED): PurchaseOrder
{
    $user = User::factory()->create();

    auth()->login($user);

    $company = Company::query()->create([
        'code' => 'CMP-TEST',
        'name' => 'Test Company',
        'description' => '',
        'alias' => 'TC',
        'address' => 'Jl. Test Company',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Tester',
        'contact_person_position' => 'Manager',
        'phone' => '021000000',
        'fax' => '',
        'email' => 'company@example.test',
        'website' => 'https://company.test',
        'tax_number' => 'NPWP-COMPANY',
        'is_active' => true,
    ]);
    $warehouse = Warehouse::query()->create([
        'code' => 'WH-TEST',
        'name' => 'Test Warehouse',
        'description' => '',
        'is_active' => true,
    ]);
    $warehouseAddress = WarehouseAddress::query()->create([
        'warehouse_id' => $warehouse->id,
        'address' => 'Jl. Gudang Test',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'phone' => '',
        'fax' => '',
        'as_default' => true,
    ]);
    $division = Division::query()->create([
        'code' => 'DIV-TEST',
        'name' => 'Test Division',
        'description' => '',
        'is_active' => true,
    ]);
    $project = Project::query()->create([
        'code' => 'PRJ-TEST',
        'po_code' => 'PO-TEST',
        'name' => 'Test Project',
        'description' => '',
        'allow_po' => true,
        'is_active' => true,
    ]);
    $vendor = Vendor::query()->create([
        'code' => 'VND-TEST',
        'name' => 'Test Vendor',
        'description' => '',
        'address' => 'Jl. Vendor Test',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Vendor PIC',
        'contact_person_position' => 'Sales',
        'phone' => '021111111',
        'fax' => '',
        'email' => 'vendor@example.test',
        'website' => 'https://vendor.test',
        'tax_number' => 'NPWP-VENDOR',
        'is_active' => true,
    ]);
    $category = ItemCategory::query()->create([
        'parent_id' => null,
        'level' => ItemCategory::LEVEL_SUB_CATEGORY,
        'code' => 'CAT-TEST',
        'name' => 'Test Category',
        'description' => '',
        'allow_po' => true,
    ]);
    $item = Item::query()->create([
        'category_id' => $category->id,
        'code' => 'ITM-TEST',
        'name' => 'Test Item',
        'description' => '',
        'unit' => 'pcs',
        'type' => Item::TYPE_CONSUMABLE,
        'is_active' => true,
    ]);

    $purchaseRequest = PurchaseRequest::query()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'warehouse_address_id' => $warehouseAddress->id,
        'division_id' => $division->id,
        'project_id' => $project->id,
        'description' => 'Purchase request for edit test',
        'memo' => '',
        'boq' => '',
        'notes' => '',
        'info' => '',
    ]);
    $purchaseRequest->update([
        'status' => $purchaseRequestStatus,
    ]);

    $purchaseRequestItem = PurchaseRequestItem::query()->create([
        'purchase_request_id' => $purchaseRequest->id,
        'item_id' => $item->id,
        'qty' => 10,
        'description' => 'Requested item',
        'sort' => 1,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'vendor_id' => $vendor->id,
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'warehouse_address_id' => $warehouseAddress->id,
        'division_id' => $division->id,
        'project_id' => $project->id,
        'description' => 'Purchase order for edit test',
        'memo' => '',
        'termin' => '',
        'delivery_info' => '',
        'notes' => '',
        'info' => '',
        'discount' => 50000,
        'tax_type' => PurchaseOrderTaxType::EXCLUDE,
        'tax_percentage' => PurchaseOrder::DEFAULT_TAX_PERCENTAGE,
        'tax' => 1100,
        'tax_description' => '',
        'rounding' => 0,
    ]);

    $purchaseOrder->purchaseRequests()->sync([$purchaseRequest->id]);
    $purchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => $purchaseRequestItem->id,
        'item_id' => $item->id,
        'qty' => 1,
        'price' => 10000,
        'discount' => 0,
        'description' => 'Ordered item',
        'sort' => 1,
    ]);

    return $purchaseOrder->fresh([
        'purchaseRequests',
        'purchaseOrderItems',
    ]);
}
