<?php

use App\Enums\PurchaseOrderTaxType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Filament\Resources\PurchaseOrders\Schemas\PurchaseOrderInfolist;
use App\Filament\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Livewire\PurchaseOrderItemsTable;
use App\Models\Company;
use App\Models\Division;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Project;
use App\Models\Role;
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

it('calculates item total from quantity and price', function () {
    expect(PurchaseOrder::calculateItemTotal([
        'qty' => 5,
        'price' => 10000,
    ]))->toBe(50000.0);
});

it('does not allow negative item total', function () {
    expect(PurchaseOrder::calculateItemTotal([
        'qty' => -1,
        'price' => 5000,
    ]))->toBe(0.0);
});

it('calculates purchase order summary for exclude tax', function () {
    $items = [
        [
            'qty' => 2,
            'price' => 10000,
        ],
        [
            'qty' => 3,
            'price' => 5000,
        ],
    ];

    $summary = PurchaseOrder::calculateOrderSummary($items, 3500, PurchaseOrderTaxType::EXCLUDE, 11, 200);

    expect(PurchaseOrder::calculateSubtotal($items))->toBe(35000.0)
        ->and(PurchaseOrder::calculateTotalSubtotal($items, PurchaseOrderTaxType::EXCLUDE, 11))->toBe(35000.0)
        ->and(PurchaseOrder::calculateNetSubtotal($items, 3500))->toBe(31500.0)
        ->and(PurchaseOrder::calculateSubtotalDiscount($items, 3500, PurchaseOrderTaxType::EXCLUDE, 11))->toBe(3500.0)
        ->and(PurchaseOrder::calculateSubtotalTax($items, 3500, PurchaseOrderTaxType::EXCLUDE, 11))->toBe(3465.0)
        ->and(PurchaseOrder::calculateTotalBeforeRounding($items, 3500, PurchaseOrderTaxType::EXCLUDE, 11))->toBe(34965.0)
        ->and(PurchaseOrder::calculateGrandTotal($items, 3500, PurchaseOrderTaxType::EXCLUDE, 11, 200))->toBe(35165.0)
        ->and($summary)->toMatchArray([
            'subtotal' => 35000.0,
            'discount' => 3500.0,
            'subtotal_after_discount' => 31500.0,
            'dpp' => 31500.0,
            'tax_amount' => 3465.0,
            'total_before_rounding' => 34965.0,
            'rounding' => 200.0,
            'grand_total' => 35165.0,
        ]);
});

it('calculates purchase order summary for include tax', function () {
    $items = [
        [
            'qty' => 1,
            'price' => 111000,
        ],
    ];

    $summary = PurchaseOrder::calculateOrderSummary($items, 0, PurchaseOrderTaxType::INCLUDE, 11, 0);

    expect(PurchaseOrder::calculateTotalSubtotal($items, PurchaseOrderTaxType::INCLUDE, 11))->toBe(111000.0)
        ->and(PurchaseOrder::calculateSubtotalTax($items, 0, PurchaseOrderTaxType::INCLUDE, 11))->toBe(11000.0)
        ->and(PurchaseOrder::calculateGrandTotal($items, 0, PurchaseOrderTaxType::INCLUDE, 11, 0))->toBe(111000.0)
        ->and($summary)->toMatchArray([
            'subtotal' => 111000.0,
            'discount' => 0.0,
            'subtotal_after_discount' => 111000.0,
            'dpp' => 100000.0,
            'tax_amount' => 11000.0,
            'total_before_rounding' => 111000.0,
            'rounding' => 0.0,
            'grand_total' => 111000.0,
        ]);
});

it('applies discount before include tax breakdown', function () {
    $items = [
        [
            'qty' => 1,
            'price' => 111000,
        ],
    ];

    $orderDiscount = 11100;

    $summary = PurchaseOrder::calculateOrderSummary($items, $orderDiscount, PurchaseOrderTaxType::INCLUDE, 11, 0);

    expect($summary)->toMatchArray([
        'subtotal' => 111000.0,
        'discount' => 11100.0,
        'subtotal_after_discount' => 99900.0,
        'dpp' => 90000.0,
        'tax_amount' => 9900.0,
        'total_before_rounding' => 99900.0,
        'rounding' => 0.0,
        'grand_total' => 99900.0,
    ]);
});

it('rounds tax using subtotal after discount', function () {
    $items = [
        [
            'qty' => 1,
            'price' => 100001,
        ],
    ];

    expect(PurchaseOrder::calculateSubtotalTax($items, 0, PurchaseOrderTaxType::INCLUDE, 11))->toBe(9910.0)
        ->and(PurchaseOrder::calculateSubtotalTax($items, 0, PurchaseOrderTaxType::EXCLUDE, 11))->toBe(11000.0);
});

it('limits discount to subtotal in order summary', function () {
    $items = [
        [
            'qty' => 1,
            'price' => 1,
        ],
        [
            'qty' => 1,
            'price' => 5,
        ],
    ];

    expect(PurchaseOrder::calculateOrderSummary($items, 10, PurchaseOrderTaxType::EXCLUDE, 11, 0))
        ->toMatchArray([
            'subtotal' => 6.0,
            'discount' => 6.0,
            'subtotal_after_discount' => 0.0,
            'dpp' => 0.0,
            'tax_amount' => 0.0,
            'total_before_rounding' => 0.0,
            'rounding' => 0.0,
            'grand_total' => 0.0,
        ]);
});

it('keeps line breakdown simple and summary driven', function () {
    $items = [
        [
            'line_key' => 'line-1',
            'qty' => 1,
            'price' => 10000,
        ],
        [
            'line_key' => 'line-2',
            'qty' => 1,
            'price' => 5000,
        ],
    ];

    $breakdown = PurchaseOrder::calculateOrderBreakdown(
        $items,
        1500,
        PurchaseOrderTaxType::EXCLUDE,
        11,
        0,
    );

    expect($breakdown['lines']['line-1']['discount'])->toBe(0.0)
        ->and($breakdown['lines']['line-1']['subtotal'])->toBe(10000.0)
        ->and($breakdown['lines']['line-1']['subtotal_after_discount'])->toBe(10000.0)
        ->and($breakdown['lines']['line-1']['dpp'])->toBe(10000.0)
        ->and($breakdown['lines']['line-1']['tax_amount'])->toBe(0.0)
        ->and($breakdown['lines']['line-1']['total_before_rounding'])->toBe(10000.0)
        ->and($breakdown['lines']['line-1']['grand_total'])->toBe(10000.0)
        ->and($breakdown['lines']['line-2']['discount'])->toBe(0.0)
        ->and($breakdown['lines']['line-2']['subtotal'])->toBe(5000.0)
        ->and($breakdown['lines']['line-2']['subtotal_after_discount'])->toBe(5000.0)
        ->and($breakdown['lines']['line-2']['dpp'])->toBe(5000.0)
        ->and($breakdown['lines']['line-2']['tax_amount'])->toBe(0.0)
        ->and($breakdown['lines']['line-2']['total_before_rounding'])->toBe(5000.0)
        ->and($breakdown['lines']['line-2']['grand_total'])->toBe(5000.0)
        ->and($breakdown['summary']['subtotal'])->toBe(15000.0)
        ->and($breakdown['summary']['discount'])->toBe(1500.0)
        ->and($breakdown['summary']['subtotal_after_discount'])->toBe(13500.0)
        ->and($breakdown['summary']['dpp'])->toBe(13500.0)
        ->and($breakdown['summary']['tax_amount'])->toBe(1485.0)
        ->and($breakdown['summary']['total_before_rounding'])->toBe(14985.0)
        ->and($breakdown['summary']['grand_total'])->toBe(14985.0);
});

it('uses indonesian effective ppn calculation for 12 percent exclude tax purchase orders', function () {
    $items = [
        [
            'qty' => 1,
            'price' => 100000,
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
        ],
    ];

    expect(PurchaseOrder::calculateTotalSubtotal($items, PurchaseOrderTaxType::INCLUDE, 12))->toBe(111000.0);
    expect(PurchaseOrder::calculateSubtotalTax($items, 0, PurchaseOrderTaxType::INCLUDE, 12))->toBe(11000.0);
    expect(PurchaseOrder::calculateGrandTotal($items, 0, PurchaseOrderTaxType::INCLUDE, 12, 0))->toBe(111000.0);
});

it('keeps model total accessors aligned with order breakdown summary', function () {
    $purchaseOrder = createEditablePurchaseOrder();

    $purchaseOrder->update([
        'discount' => 11100,
        'tax_type' => PurchaseOrderTaxType::INCLUDE,
        'tax_percentage' => 11,
        'rounding' => 100,
    ]);

    $purchaseOrder->purchaseOrderItems()->firstOrFail()->update([
        'qty' => 1,
        'price' => 111000,
    ]);

    $purchaseOrder->refresh()->load('purchaseOrderItems');

    $summary = PurchaseOrder::calculateOrderSummary(
        $purchaseOrder->purchaseOrderItems->map(fn(PurchaseOrderItem $item): array => [
            'id' => $item->id,
            'purchase_request_item_id' => $item->purchase_request_item_id,
            'qty' => (float) $item->qty,
            'price' => (float) $item->price,
        ])->all(),
        (float) $purchaseOrder->discount,
        $purchaseOrder->tax_type,
        (float) $purchaseOrder->tax_percentage,
        (float) $purchaseOrder->rounding,
    );

    expect($purchaseOrder->getSubtotalAmount())->toBe($summary['subtotal'])
        ->and($purchaseOrder->getNetSubtotalAmount())->toBe($summary['subtotal_after_discount'])
        ->and($purchaseOrder->getTaxAmount())->toBe($summary['tax_amount'])
        ->and($purchaseOrder->getGrandTotalAmount())->toBe($summary['grand_total']);
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

it('recalculates totals when sourced item values are edited on save', function () {
    $purchaseOrder = createEditablePurchaseOrder();
    $updatedQty = 3;
    $updatedPrice = 15000;
    $updatedRounding = 50;
    $firstPurchaseOrderItemKey = null;

    Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertFormSet(function (array $state) use (&$firstPurchaseOrderItemKey): array {
            $firstPurchaseOrderItemKey = array_key_first($state['purchaseOrderItems']);

            return [];
        })
        ->set("data.purchaseOrderItems.{$firstPurchaseOrderItemKey}.qty", $updatedQty)
        ->set("data.purchaseOrderItems.{$firstPurchaseOrderItemKey}.price", $updatedPrice)
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
        ]],
        0,
        PurchaseOrderTaxType::EXCLUDE,
        PurchaseOrder::DEFAULT_TAX_PERCENTAGE,
        $updatedRounding,
    );

    expect((float) $purchaseOrderItem->qty)->toBe((float) $updatedQty)
        ->and((float) $purchaseOrderItem->price)->toBe((float) $updatedPrice)
        ->and((float) $purchaseOrder->discount)->toBe(0.0)
        ->and((float) $purchaseOrder->tax)->toBe($expectedTax)
        ->and(PurchaseOrder::calculateGrandTotal(
            $purchaseOrder->purchaseOrderItems()
                ->get()
                ->map(fn(PurchaseOrderItem $item): array => [
                    'purchase_request_item_id' => $item->purchase_request_item_id,
                    'qty' => (float) $item->qty,
                    'price' => (float) $item->price,
                ])
                ->all(),
            (float) $purchaseOrder->discount,
            $purchaseOrder->tax_type,
            (float) $purchaseOrder->tax_percentage,
            (float) $purchaseOrder->rounding,
        ))->toBe($expectedGrandTotal);
});

it('allows saving purchase order without tax percentage', function () {
    $purchaseOrder = createEditablePurchaseOrder();
    $firstPurchaseOrderItemKey = null;

    Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertFormSet(function (array $state) use (&$firstPurchaseOrderItemKey): array {
            $firstPurchaseOrderItemKey = array_key_first($state['purchaseOrderItems']);

            return [];
        })
        ->set('data.tax_percentage', null)
        ->set("data.purchaseOrderItems.{$firstPurchaseOrderItemKey}.price", 10000)
        ->call('save', false, false)
        ->assertHasNoFormErrors();

    $purchaseOrder->refresh();

    expect($purchaseOrder->tax_percentage)->toBeNull()
        ->and((float) $purchaseOrder->tax)->toBe(0.0);
});

it('prevents saving purchase order when purchase request status changes after form is loaded', function () {
    $purchaseOrder = createEditablePurchaseOrder();
    $purchaseRequest = $purchaseOrder->purchaseRequests->firstOrFail();

    $component = Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()]);

    $purchaseRequest->update([
        'status' => PurchaseRequestStatus::FINISHED,
    ]);

    $component
        ->call('save', false, false)
        ->assertHasFormErrors();
});

it('prevents saving purchase order when purchase request item request quantity changes after form is loaded', function () {
    $purchaseOrder = createEditablePurchaseOrder();
    $purchaseRequestItem = $purchaseOrder->purchaseOrderItems->firstOrFail()->purchaseRequestItem;
    $firstPurchaseOrderItemKey = null;

    $component = Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertFormSet(function (array $state) use (&$firstPurchaseOrderItemKey): array {
            $firstPurchaseOrderItemKey = array_key_first($state['purchaseOrderItems']);

            return [];
        });

    $purchaseRequestItem->update([
        'qty' => 12,
    ]);

    $component
        ->call('save', false, false)
        ->assertHasFormErrors();
});

it('prevents saving purchase order when purchase request item ordered quantity changes after form is loaded', function () {
    $purchaseOrder = createEditablePurchaseOrder();
    $purchaseOrderItem = $purchaseOrder->purchaseOrderItems->firstOrFail();
    $firstPurchaseOrderItemKey = null;

    $component = Livewire::test(EditPurchaseOrder::class, ['record' => $purchaseOrder->getRouteKey()])
        ->assertFormSet(function (array $state) use (&$firstPurchaseOrderItemKey): array {
            $firstPurchaseOrderItemKey = array_key_first($state['purchaseOrderItems']);

            return [];
        });

    $anotherPurchaseOrder = PurchaseOrder::query()->create([
        'vendor_id' => $purchaseOrder->vendor_id,
        'company_id' => $purchaseOrder->company_id,
        'warehouse_id' => $purchaseOrder->warehouse_id,
        'warehouse_address_id' => $purchaseOrder->warehouse_address_id,
        'division_id' => $purchaseOrder->division_id,
        'project_id' => $purchaseOrder->project_id,
        'description' => 'Concurrent PO',
        'memo' => '',
        'termin' => '',
        'delivery_info' => '',
        'notes' => '',
        'info' => '',
        'discount' => 0,
        'tax_type' => PurchaseOrderTaxType::EXCLUDE,
        'tax_percentage' => PurchaseOrder::DEFAULT_TAX_PERCENTAGE,
        'tax' => 0,
        'tax_description' => '',
        'rounding' => 0,
    ]);

    $anotherPurchaseOrder->purchaseRequests()->sync($purchaseOrder->purchaseRequests->pluck('id')->all());
    $anotherPurchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => $purchaseOrderItem->purchase_request_item_id,
        'item_id' => $purchaseOrderItem->item_id,
        'qty' => 1,
        'price' => 10000,
        'description' => 'Concurrent ordered item',
        'sort' => 1,
    ]);

    $component
        ->call('save', false, false)
        ->assertHasFormErrors();
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

it('marks purchase order and selected purchase requests as ordered with status logs', function () {
    $purchaseOrder = createEditablePurchaseOrder();
    $user = auth()->user();

    Role::findOrCreate(Role::PURCHASING, 'web');
    $user->assignRole(Role::PURCHASING);

    $purchaseOrder->markAsOrdered();
    $purchaseOrder->refresh();
    $purchaseRequest = $purchaseOrder->purchaseRequests()->firstOrFail()->fresh();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::ORDERED)
        ->and($purchaseRequest->status)->toBe(PurchaseRequestStatus::ORDERED)
        ->and($purchaseOrder->statusLogs()->latest('id')->first()?->to_status?->value)->toBe(PurchaseOrderStatus::ORDERED->value)
        ->and($purchaseRequest->statusLogs()->latest('id')->first()?->to_status?->value)->toBe(PurchaseRequestStatus::ORDERED->value);
});

it('renders purchase order item summary with spacing and muted metadata', function () {
    $purchaseOrder = createEditablePurchaseOrder();
    $purchaseOrderItem = $purchaseOrder->purchaseOrderItems()->with('purchaseRequestItem.purchaseRequest')->firstOrFail();

    $renderedSummary = PurchaseOrderInfolist::purchaseOrderItemSummaryView($purchaseOrderItem)->render();

    expect($renderedSummary)
        ->toContain('space-y-1.5')
        ->toContain('font-medium text-gray-950')
        ->toContain('text-sm leading-5 text-gray-500')
        ->toContain('font-mono text-xs text-gray-500')
        ->toContain('Test Item')
        ->toContain('Ordered item')
        ->toContain('PR: ' . $purchaseOrder->purchaseRequests()->firstOrFail()->number);
});

it('renders purchase order items table with source purchase request number and subtotal', function () {
    $purchaseOrder = createEditablePurchaseOrder();
    $purchaseRequest = $purchaseOrder->purchaseRequests()->firstOrFail();

    Livewire::test(PurchaseOrderItemsTable::class, ['record' => $purchaseOrder])
        ->assertSuccessful()
        ->loadTable()
        ->assertSee('# ' . $purchaseRequest->number)
        ->assertSee('Ordered item')
        ->assertSee('align-top [&_td]:py-1.5 [&_th]:py-1.5', escape: false)
        ->assertSee('10.000,00');
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
        'discount' => 0,
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
        'description' => 'Ordered item',
        'sort' => 1,
    ]);

    return $purchaseOrder->fresh([
        'purchaseRequests',
        'purchaseOrderItems',
    ]);
}
