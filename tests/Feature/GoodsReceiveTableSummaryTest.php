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

it('aggregates received quantities for goods receive summaries', function () {
    $ctx = createGoodsReceiveSummaryContext();

    $purchaseOrder = PurchaseOrder::query()->create([
        'vendor_id' => $ctx['vendor']->id,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Purchase order for goods receive summary test',
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
        'description' => 'Goods receive summary test',
        'delivery_order' => 'DO-GR-SUMMARY-001',
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

    $returnedGoodsReceive = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::PURCHASE_ORDER,
        'purchase_order_id' => $purchaseOrder->id,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Returned goods receive summary test',
        'delivery_order' => 'DO-GR-SUMMARY-002',
        'notes' => '',
        'info' => '',
    ]);

    $returnedGoodsReceive->update([
        'status' => GoodsReceiveStatus::RETURNED,
    ]);

    $returnedGoodsReceive->goodsReceiveItems()->create([
        'purchase_order_item_id' => $firstPurchaseOrderItem->id,
        'item_id' => $ctx['item']->id,
        'qty' => 3,
        'description' => '',
        'sort' => 1,
    ]);

    $summary = GoodsReceive::query()
        ->withQuantitySummary()
        ->findOrFail($goodsReceive->id);

    expect((float) $summary->getAttribute('goods_receive_items_sum_qty'))->toBe(5.0)
        ->and($summary->getTotalReceivedQty())->toBe(5.0);
});

function createGoodsReceiveSummaryContext(): array
{
    $user = User::factory()->create();
    auth()->login($user);

    $company = Company::query()->create([
        'code' => 'CMP-GR-SUMMARY',
        'name' => 'Test Company GR Summary',
        'description' => '',
        'alias' => 'TCGRS',
        'address' => 'Jl. Test Company GR Summary',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Tester',
        'contact_person_position' => 'Manager',
        'phone' => '021000000',
        'fax' => '',
        'email' => 'company-gr-summary@example.test',
        'website' => 'https://company-gr-summary.test',
        'tax_number' => 'NPWP-COMPANY-GR-SUMMARY',
        'is_active' => true,
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => 'WH-GR-SUMMARY',
        'name' => 'Test Warehouse GR Summary',
        'description' => '',
        'is_active' => true,
    ]);

    $warehouseAddress = WarehouseAddress::query()->create([
        'warehouse_id' => $warehouse->id,
        'address' => 'Jl. Gudang GR Summary',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'phone' => '',
        'fax' => '',
        'as_default' => true,
    ]);

    $division = Division::query()->create([
        'code' => 'DIV-GR-SUMMARY',
        'name' => 'Test Division GR Summary',
        'description' => '',
        'is_active' => true,
    ]);

    $project = Project::query()->create([
        'code' => 'PRJ-GR-SUMMARY',
        'po_code' => 'GR-SUMMARY',
        'name' => 'Test Project GR Summary',
        'description' => '',
        'allow_po' => true,
        'is_active' => true,
    ]);

    $vendor = Vendor::query()->create([
        'code' => 'VND-GR-SUMMARY',
        'name' => 'Test Vendor GR Summary',
        'description' => '',
        'address' => 'Jl. Vendor GR Summary',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Vendor PIC',
        'contact_person_position' => 'Sales',
        'phone' => '021111111',
        'fax' => '',
        'email' => 'vendor-gr-summary@example.test',
        'website' => 'https://vendor-gr-summary.test',
        'tax_number' => 'NPWP-VENDOR-GR-SUMMARY',
        'is_active' => true,
    ]);

    $category = ItemCategory::query()->create([
        'parent_id' => null,
        'level' => ItemCategory::LEVEL_SUB_CATEGORY,
        'code' => 'CAT-GR-SUMMARY',
        'name' => 'Test Category GR Summary',
        'description' => '',
        'allow_po' => true,
    ]);

    $item = Item::query()->create([
        'category_id' => $category->id,
        'code' => 'ITM-GR-SUMMARY',
        'name' => 'Test Item GR Summary',
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
