<?php

use App\Enums\GoodsReceiveStatus;
use App\Enums\GoodsReceiveType;
use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrderItems\PurchaseOrderItemResource;
use App\Models\Company;
use App\Models\Division;
use App\Models\GoodsReceive;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('aggregates received quantities for purchase order item summaries', function () {
    $context = createPurchaseOrderItemContext('SUM');

    $purchaseOrder = createPurchaseOrderForItemContext($context, 'SUM-1', PurchaseOrderStatus::ORDERED);

    $purchaseOrderItem = $purchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => null,
        'item_id' => $context['item']->id,
        'qty' => 10,
        'price' => 12000,
        'description' => 'Summary line',
        'sort' => 1,
    ]);

    $goodsReceive = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::PURCHASE_ORDER,
        'purchase_order_id' => $purchaseOrder->id,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Partial receive',
        'delivery_order' => 'DO-POI-SUM-1',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceive->update(['status' => GoodsReceiveStatus::CONFIRMED]);
    $goodsReceive->goodsReceiveItems()->create([
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'item_id' => $context['item']->id,
        'qty' => 4,
        'description' => '',
        'sort' => 1,
    ]);

    $canceledGoodsReceive = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::PURCHASE_ORDER,
        'purchase_order_id' => $purchaseOrder->id,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Canceled receive',
        'delivery_order' => 'DO-POI-SUM-2',
        'notes' => '',
        'info' => '',
    ]);

    $canceledGoodsReceive->update(['status' => GoodsReceiveStatus::CANCELED]);
    $canceledGoodsReceive->goodsReceiveItems()->create([
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'item_id' => $context['item']->id,
        'qty' => 2,
        'description' => '',
        'sort' => 1,
    ]);

    $summary = PurchaseOrderItem::query()
        ->withQuantitySummary()
        ->findOrFail($purchaseOrderItem->id);

    expect((float) $summary->qty)->toBe(10.0)
        ->and((float) $summary->price)->toBe(12000.0)
        ->and($summary->getSubtotalAmount())->toBe(120000.0)
        ->and($summary->getTotalReceivedQty())->toBe(4.0)
        ->and($summary->getRemainingReceivedQty())->toBe(6.0)
        ->and($summary->getReceivedPercentage())->toBe(40.0);
});

it('loads purchase order item resource rows with purchase order and vendor context', function () {
    $context = createPurchaseOrderItemContext('CTX');

    $purchaseOrder = createPurchaseOrderForItemContext($context, 'CTX-1', PurchaseOrderStatus::ORDERED);

    $purchaseOrderItem = $purchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => null,
        'item_id' => $context['item']->id,
        'qty' => 7,
        'price' => 15000,
        'description' => 'Context line',
        'sort' => 1,
    ]);

    $row = PurchaseOrderItemResource::getEloquentQuery()->findOrFail($purchaseOrderItem->id);

    expect($row->purchaseOrder?->number)->toBe($purchaseOrder->number)
        ->and($row->purchaseOrder?->vendor?->name)->toBe($context['vendor']->name)
        ->and($row->getSubtotalAmount())->toBe(105000.0);
});

function createPurchaseOrderItemContext(string $suffix): array
{
    $suffix = strtoupper($suffix);
    $emailSuffix = strtolower($suffix);

    $user = User::query()->create([
        'name' => "PO Item Tester {$suffix}",
        'email' => "po-item-tester-{$emailSuffix}@example.test",
        'password' => 'password',
        'email_verified_at' => now(),
        'avatar_url' => '',
    ]);

    auth()->login($user);

    $company = Company::query()->create([
        'code' => "CMP-POI-{$suffix}",
        'name' => "Company {$suffix}",
        'description' => '',
        'alias' => "C{$suffix}",
        'address' => "Address {$suffix}",
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Tester',
        'contact_person_position' => 'Manager',
        'phone' => '021000000',
        'fax' => '',
        'email' => "company-po-item-{$emailSuffix}@example.test",
        'website' => 'https://example.test',
        'tax_number' => "NPWP-POI-{$suffix}",
        'is_active' => true,
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => "WH-POI-{$suffix}",
        'name' => "Warehouse {$suffix}",
        'description' => '',
        'is_active' => true,
    ]);

    $warehouseAddress = WarehouseAddress::query()->create([
        'warehouse_id' => $warehouse->id,
        'address' => "Address Warehouse {$suffix}",
        'city' => 'Jakarta',
        'post_code' => '10110',
        'phone' => '',
        'fax' => '',
        'as_default' => true,
    ]);

    $division = Division::query()->create([
        'code' => "DIV-POI-{$suffix}",
        'name' => "Division {$suffix}",
        'description' => '',
        'is_active' => true,
    ]);

    $project = Project::query()->create([
        'code' => "PRJ-POI-{$suffix}",
        'po_code' => "PO-POI-{$suffix}",
        'name' => "Project {$suffix}",
        'description' => '',
        'allow_po' => true,
        'is_active' => true,
    ]);

    $vendor = Vendor::query()->create([
        'code' => "VND-POI-{$suffix}",
        'name' => "Vendor {$suffix}",
        'description' => '',
        'address' => "Vendor Address {$suffix}",
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Vendor PIC',
        'contact_person_position' => 'Sales',
        'phone' => '021111111',
        'fax' => '',
        'email' => "vendor-po-item-{$emailSuffix}@example.test",
        'website' => 'https://example.test',
        'tax_number' => "NPWP-VND-POI-{$suffix}",
        'is_active' => true,
    ]);

    $category = ItemCategory::query()->create([
        'parent_id' => null,
        'level' => ItemCategory::LEVEL_SUB_CATEGORY,
        'code' => "CAT-POI-{$suffix}",
        'name' => "Category {$suffix}",
        'description' => '',
        'allow_po' => true,
    ]);

    $item = Item::query()->create([
        'category_id' => $category->id,
        'code' => "ITM-POI-{$suffix}",
        'name' => "Item {$suffix}",
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

function createPurchaseOrderForItemContext(array $context, string $suffix, PurchaseOrderStatus $status): PurchaseOrder
{
    $purchaseOrder = PurchaseOrder::query()->create([
        'vendor_id' => $context['vendor']->id,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => "Purchase order {$suffix}",
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

    $purchaseOrder->update(['status' => $status]);

    return $purchaseOrder;
}
