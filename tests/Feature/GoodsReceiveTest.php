<?php

use App\Enums\GoodsReceiveStatus;
use App\Enums\GoodsReceiveType;
use App\Models\Company;
use App\Models\Division;
use App\Models\GoodsReceive;
use App\Models\GoodsReceiveItem;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

it('supports partial receiving across multiple goods receives for one purchase order', function () {
    $ctx = createPurchaseOrderContext();

    $purchaseOrderItem = $ctx['purchaseOrder']->purchaseOrderItems()->firstOrFail();

    $goodsReceive1 = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::PURCHASE_ORDER,
        'purchase_order_id' => $ctx['purchaseOrder']->id,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Receiving batch 1',
        'delivery_order' => 'DO-001',
        'notes' => '',
        'info' => '',
        'status' => GoodsReceiveStatus::RECEIVED,
    ]);

    $goodsReceive1->goodsReceiveItems()->create([
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'item_id' => $purchaseOrderItem->item_id,
        'qty' => 6,
        'description' => 'Received partial',
        'sort' => 1,
    ]);

    $purchaseOrderItem->refresh();

    expect($purchaseOrderItem->getReceivedQty())->toBe(6.0)
        ->and($purchaseOrderItem->getRemainingReceiveQty())->toBe(4.0);

    $goodsReceive2 = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::PURCHASE_ORDER,
        'purchase_order_id' => $ctx['purchaseOrder']->id,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Receiving batch 2',
        'delivery_order' => 'DO-002',
        'notes' => '',
        'info' => '',
        'status' => GoodsReceiveStatus::RECEIVED,
    ]);

    $goodsReceive2->goodsReceiveItems()->create([
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'item_id' => $purchaseOrderItem->item_id,
        'qty' => 4,
        'description' => 'Received remaining',
        'sort' => 1,
    ]);

    $purchaseOrderItem->refresh();

    expect($purchaseOrderItem->getReceivedQty())->toBe(10.0)
        ->and($purchaseOrderItem->getRemainingReceiveQty())->toBe(0.0);
});

it('prevents receiving quantity exceeding remaining purchase order item quantity', function () {
    $ctx = createPurchaseOrderContext();
    $purchaseOrderItem = $ctx['purchaseOrder']->purchaseOrderItems()->firstOrFail();

    $goodsReceive = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::PURCHASE_ORDER,
        'purchase_order_id' => $ctx['purchaseOrder']->id,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Receiving',
        'delivery_order' => 'DO-003',
        'notes' => '',
        'info' => '',
        'status' => GoodsReceiveStatus::RECEIVED,
    ]);

    $goodsReceive->goodsReceiveItems()->create([
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'item_id' => $purchaseOrderItem->item_id,
        'qty' => 6,
        'description' => 'Received partial',
        'sort' => 1,
    ]);

    expect(fn () => GoodsReceive::validateAllocationQuantities([
        [
            'purchase_order_item_id' => $purchaseOrderItem->id,
            'item_id' => $purchaseOrderItem->item_id,
            'qty' => 5,
        ],
    ]))->toThrow(ValidationException::class);
});

it('does not count canceled goods receive items as received quantity', function () {
    $ctx = createPurchaseOrderContext();
    $purchaseOrderItem = $ctx['purchaseOrder']->purchaseOrderItems()->firstOrFail();

    $goodsReceive = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::PURCHASE_ORDER,
        'purchase_order_id' => $ctx['purchaseOrder']->id,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Canceled receive',
        'delivery_order' => 'DO-004',
        'notes' => '',
        'info' => '',
        'status' => GoodsReceiveStatus::RECEIVED,
    ]);

    $goodsReceive->goodsReceiveItems()->create([
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'item_id' => $purchaseOrderItem->item_id,
        'qty' => 3,
        'description' => 'Canceled receipt',
        'sort' => 1,
    ]);

    $goodsReceive->update([
        'status' => GoodsReceiveStatus::CANCELED,
    ]);

    $purchaseOrderItem->refresh();

    expect($purchaseOrderItem->getReceivedQty())->toBe(0.0)
        ->and($purchaseOrderItem->getRemainingReceiveQty())->toBe(10.0);
});


function createPurchaseOrderContext(): array
{
    $user = User::factory()->create();
    auth()->login($user);

    $company = Company::query()->create([
        'code' => 'CMP-GR-TEST',
        'name' => 'Test Company GR',
        'description' => '',
        'alias' => 'TCGR',
        'address' => 'Jl. Test Company GR',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Tester',
        'contact_person_position' => 'Manager',
        'phone' => '021000000',
        'fax' => '',
        'email' => 'company-gr@example.test',
        'website' => 'https://company-gr.test',
        'tax_number' => 'NPWP-COMPANY-GR',
        'is_active' => true,
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => 'WH-GR-TEST',
        'name' => 'Test Warehouse GR',
        'description' => '',
        'is_active' => true,
    ]);

    $warehouseAddress = WarehouseAddress::query()->create([
        'warehouse_id' => $warehouse->id,
        'address' => 'Jl. Gudang GR',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'phone' => '',
        'fax' => '',
        'as_default' => true,
    ]);

    $division = Division::query()->create([
        'code' => 'DIV-GR-TEST',
        'name' => 'Test Division GR',
        'description' => '',
        'is_active' => true,
    ]);

    $project = Project::query()->create([
        'code' => 'PRJ-GR-TEST',
        'po_code' => 'PO-GR-TEST',
        'name' => 'Test Project GR',
        'description' => '',
        'allow_po' => true,
        'is_active' => true,
    ]);

    $vendor = Vendor::query()->create([
        'code' => 'VND-GR-TEST',
        'name' => 'Test Vendor GR',
        'description' => '',
        'address' => 'Jl. Vendor GR',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Vendor PIC',
        'contact_person_position' => 'Sales',
        'phone' => '021111111',
        'fax' => '',
        'email' => 'vendor-gr@example.test',
        'website' => 'https://vendor-gr.test',
        'tax_number' => 'NPWP-VENDOR-GR',
        'is_active' => true,
    ]);

    $category = ItemCategory::query()->create([
        'parent_id' => null,
        'level' => ItemCategory::LEVEL_SUB_CATEGORY,
        'code' => 'CAT-GR-TEST',
        'name' => 'Test Category GR',
        'description' => '',
        'allow_po' => true,
    ]);

    $item = Item::query()->create([
        'category_id' => $category->id,
        'code' => 'ITM-GR-TEST',
        'name' => 'Test Item GR',
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
        'description' => 'Purchase request for GR test',
        'memo' => '',
        'boq' => '',
        'notes' => '',
        'info' => '',
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
        'description' => 'Purchase order for GR test',
        'delivery_date' => now()->toDateString(),
        'delivery_notes' => '',
        'shipping_cost' => 0,
        'shipping_method' => '',
        'notes' => '',
        'terms' => '',
        'info' => '',
        'discount' => 0,
        'tax_type' => \App\Enums\PurchaseOrderTaxType::EXCLUDE,
        'tax_percentage' => PurchaseOrder::DEFAULT_TAX_PERCENTAGE,
        'tax_description' => '',
        'rounding' => 0,
    ]);

    $purchaseOrder->purchaseRequests()->sync([$purchaseRequest->id]);

    $purchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => $purchaseRequestItem->id,
        'item_id' => $item->id,
        'qty' => 10,
        'price' => 10000,
        'description' => 'Ordered item',
        'sort' => 1,
    ]);

    return [
        'user' => $user,
        'company' => $company,
        'warehouse' => $warehouse,
        'warehouseAddress' => $warehouseAddress,
        'division' => $division,
        'project' => $project,
        'vendor' => $vendor,
        'item' => $item,
        'purchaseRequest' => $purchaseRequest,
        'purchaseOrder' => $purchaseOrder->fresh(['purchaseOrderItems']),
    ];
}
