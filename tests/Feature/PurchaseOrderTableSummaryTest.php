<?php

use App\Enums\GoodsReceiveStatus;
use App\Enums\GoodsReceiveType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Company;
use App\Models\Division;
use App\Models\GoodsReceive;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('aggregates ordered and received quantities for purchase order summaries', function () {
    $ctx = createPurchaseOrderSummaryContext();

    $purchaseOrder = PurchaseOrder::query()->create([
        'vendor_id' => $ctx['vendor']->id,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Purchase order summary test',
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

    $purchaseOrder->update([
        'status' => PurchaseOrderStatus::ORDERED,
    ]);

    $firstPurchaseOrderItem = $purchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => null,
        'item_id' => $ctx['item']->id,
        'qty' => 10,
        'price' => 10000,
        'description' => '',
        'sort' => 1,
    ]);

    $secondPurchaseOrderItem = $purchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => null,
        'item_id' => $ctx['item']->id,
        'qty' => 5,
        'price' => 10000,
        'description' => '',
        'sort' => 2,
    ]);

    $goodsReceive = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::PURCHASE_ORDER,
        'purchase_order_id' => $purchaseOrder->id,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Summary receive',
        'delivery_order' => 'DO-PO-SUMMARY-001',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceive->goodsReceiveItems()->create([
        'purchase_order_item_id' => $firstPurchaseOrderItem->id,
        'item_id' => $ctx['item']->id,
        'qty' => 4,
        'description' => '',
        'sort' => 1,
    ]);

    $goodsReceive->goodsReceiveItems()->create([
        'purchase_order_item_id' => $secondPurchaseOrderItem->id,
        'item_id' => $ctx['item']->id,
        'qty' => 1,
        'description' => '',
        'sort' => 2,
    ]);

    $canceledGoodsReceive = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::PURCHASE_ORDER,
        'purchase_order_id' => $purchaseOrder->id,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Canceled summary receive',
        'delivery_order' => 'DO-PO-SUMMARY-002',
        'notes' => '',
        'info' => '',
    ]);

    $canceledGoodsReceive->update([
        'status' => GoodsReceiveStatus::CANCELED,
    ]);

    $canceledGoodsReceive->goodsReceiveItems()->create([
        'purchase_order_item_id' => $firstPurchaseOrderItem->id,
        'item_id' => $ctx['item']->id,
        'qty' => 3,
        'description' => '',
        'sort' => 1,
    ]);

    $summary = PurchaseOrder::query()
        ->withQuantitySummary()
        ->findOrFail($purchaseOrder->id);

    expect($summary->getTotalOrderedQty())->toBe(15.0)
        ->and($summary->getTotalReceivedQty())->toBe(5.0)
        ->and((float) $summary->getAttribute('purchase_order_items_received_percentage'))->toBe(33.33)
        ->and($summary->getReceivedPercentage())->toBe(33.33);
});

function createPurchaseOrderSummaryContext(): array
{
    $user = User::factory()->create();
    auth()->login($user);

    $company = Company::query()->create([
        'code' => 'CMP-PO-SUMMARY',
        'name' => 'Test Company PO Summary',
        'description' => '',
        'alias' => 'TCPS',
        'address' => 'Jl. Test Company PO Summary',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Tester',
        'contact_person_position' => 'Manager',
        'phone' => '021000000',
        'fax' => '',
        'email' => 'company-po-summary@example.test',
        'website' => 'https://company-po-summary.test',
        'tax_number' => 'NPWP-COMPANY-PO-SUMMARY',
        'is_active' => true,
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => 'WH-PO-SUMMARY',
        'name' => 'Test Warehouse PO Summary',
        'description' => '',
        'is_active' => true,
    ]);

    $warehouseAddress = WarehouseAddress::query()->create([
        'warehouse_id' => $warehouse->id,
        'address' => 'Jl. Gudang PO Summary',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'phone' => '',
        'fax' => '',
        'as_default' => true,
    ]);

    $division = Division::query()->create([
        'code' => 'DIV-PO-SUMMARY',
        'name' => 'Test Division PO Summary',
        'description' => '',
        'is_active' => true,
    ]);

    $project = Project::query()->create([
        'code' => 'PRJ-PO-SUMMARY',
        'po_code' => 'PO-SUMMARY',
        'name' => 'Test Project PO Summary',
        'description' => '',
        'allow_po' => true,
        'is_active' => true,
    ]);

    $vendor = Vendor::query()->create([
        'code' => 'VND-PO-SUMMARY',
        'name' => 'Test Vendor PO Summary',
        'description' => '',
        'address' => 'Jl. Vendor PO Summary',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Vendor PIC',
        'contact_person_position' => 'Sales',
        'phone' => '021111111',
        'fax' => '',
        'email' => 'vendor-po-summary@example.test',
        'website' => 'https://vendor-po-summary.test',
        'tax_number' => 'NPWP-VENDOR-PO-SUMMARY',
        'is_active' => true,
    ]);

    $category = ItemCategory::query()->create([
        'parent_id' => null,
        'level' => ItemCategory::LEVEL_SUB_CATEGORY,
        'code' => 'CAT-PO-SUMMARY',
        'name' => 'Test Category PO Summary',
        'description' => '',
        'allow_po' => true,
    ]);

    $item = Item::query()->create([
        'category_id' => $category->id,
        'code' => 'ITM-PO-SUMMARY',
        'name' => 'Test Item PO Summary',
        'description' => '',
        'unit' => 'pcs',
        'type' => Item::TYPE_CONSUMABLE,
        'is_active' => true,
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
    ];
}
