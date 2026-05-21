<?php

use App\Enums\GoodsReceiveStatus;
use App\Enums\GoodsReceiveType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Filament\Resources\PurchaseRequestItems\PurchaseRequestItemResource;
use App\Models\Company;
use App\Models\Division;
use App\Models\GoodsReceive;
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

uses(LazilyRefreshDatabase::class);

it('aggregates ordered and received quantities for purchase request item summaries', function () {
    $context = createPurchaseRequestItemContext('SUM');

    $purchaseRequest = PurchaseRequest::query()->create([
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Purchase request item summary',
        'memo' => '',
        'boq' => '',
        'notes' => '',
        'info' => '',
    ]);

    $purchaseRequest->update(['status' => PurchaseRequestStatus::ORDERED]);

    $purchaseRequestItem = $purchaseRequest->purchaseRequestItems()->create([
        'item_id' => $context['item']->id,
        'qty' => 10,
        'description' => 'Summary line',
        'sort' => 1,
    ]);

    $orderedPurchaseOrder = createPurchaseOrderForPurchaseRequestItem($context, $purchaseRequest, 'SUM-1', PurchaseOrderStatus::ORDERED);
    $orderedPurchaseOrderItem = $orderedPurchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => $purchaseRequestItem->id,
        'item_id' => $context['item']->id,
        'qty' => 5,
        'price' => 10000,
        'description' => '',
        'sort' => 1,
    ]);

    $goodsReceive = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::PURCHASE_ORDER,
        'purchase_order_id' => $orderedPurchaseOrder->id,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Partial receive',
        'delivery_order' => 'DO-PRI-SUM-1',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceive->update(['status' => GoodsReceiveStatus::CONFIRMED]);
    $goodsReceive->goodsReceiveItems()->create([
        'purchase_order_item_id' => $orderedPurchaseOrderItem->id,
        'item_id' => $context['item']->id,
        'qty' => 3,
        'description' => '',
        'sort' => 1,
    ]);

    $canceledPurchaseOrder = createPurchaseOrderForPurchaseRequestItem($context, $purchaseRequest, 'SUM-2', PurchaseOrderStatus::CANCELED);
    $canceledPurchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => $purchaseRequestItem->id,
        'item_id' => $context['item']->id,
        'qty' => 2,
        'price' => 10000,
        'description' => '',
        'sort' => 1,
    ]);

    $summary = PurchaseRequestItem::query()
        ->withQuantitySummary()
        ->findOrFail($purchaseRequestItem->id);

    expect((float) $summary->qty)->toBe(10.0)
        ->and($summary->getTotalOrderedQty())->toBe(5.0)
        ->and($summary->getRemainingOrderedQty())->toBe(5.0)
        ->and($summary->getOrderedPercentage())->toBe(50.0)
        ->and($summary->getTotalReceivedQty())->toBe(3.0)
        ->and($summary->getRemainingReceivedQty())->toBe(7.0)
        ->and($summary->getReceivedPercentage())->toBe(30.0);
});

it('can scope the purchase request item resource query to items that are not fully received', function () {
    $context = createPurchaseRequestItemContext('FLT');

    $purchaseRequest = PurchaseRequest::query()->create([
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Purchase request item filter',
        'memo' => '',
        'boq' => '',
        'notes' => '',
        'info' => '',
    ]);

    $purchaseRequest->update(['status' => PurchaseRequestStatus::ORDERED]);

    $fullyReceivedItem = $purchaseRequest->purchaseRequestItems()->create([
        'item_id' => $context['item']->id,
        'qty' => 6,
        'description' => 'Full receive',
        'sort' => 1,
    ]);

    $partiallyReceivedItem = $purchaseRequest->purchaseRequestItems()->create([
        'item_id' => $context['item']->id,
        'qty' => 8,
        'description' => 'Partial receive',
        'sort' => 2,
    ]);

    $purchaseOrder = createPurchaseOrderForPurchaseRequestItem($context, $purchaseRequest, 'FLT-1', PurchaseOrderStatus::ORDERED);

    $fullyReceivedPurchaseOrderItem = $purchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => $fullyReceivedItem->id,
        'item_id' => $context['item']->id,
        'qty' => 6,
        'price' => 10000,
        'description' => '',
        'sort' => 1,
    ]);

    $partiallyReceivedPurchaseOrderItem = $purchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => $partiallyReceivedItem->id,
        'item_id' => $context['item']->id,
        'qty' => 8,
        'price' => 10000,
        'description' => '',
        'sort' => 2,
    ]);

    $goodsReceive = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::PURCHASE_ORDER,
        'purchase_order_id' => $purchaseOrder->id,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Filter receive',
        'delivery_order' => 'DO-PRI-FLT-1',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceive->update(['status' => GoodsReceiveStatus::CONFIRMED]);
    $goodsReceive->goodsReceiveItems()->create([
        'purchase_order_item_id' => $fullyReceivedPurchaseOrderItem->id,
        'item_id' => $context['item']->id,
        'qty' => 6,
        'description' => '',
        'sort' => 1,
    ]);
    $goodsReceive->goodsReceiveItems()->create([
        'purchase_order_item_id' => $partiallyReceivedPurchaseOrderItem->id,
        'item_id' => $context['item']->id,
        'qty' => 5,
        'description' => '',
        'sort' => 2,
    ]);

    $incompleteIds = PurchaseRequestItemResource::getEloquentQuery()
        ->havingRaw('purchase_order_items_received_percentage < 100')
        ->pluck('purchase_request_items.id')
        ->all();

    expect($incompleteIds)->toBe([$partiallyReceivedItem->id]);
});

function createPurchaseRequestItemContext(string $suffix): array
{
    $suffix = strtoupper($suffix);
    $emailSuffix = strtolower($suffix);

    $user = User::query()->create([
        'name' => "PR Item Tester {$suffix}",
        'email' => "pr-item-tester-{$emailSuffix}@example.test",
        'password' => 'password',
        'email_verified_at' => now(),
        'avatar_url' => '',
    ]);

    auth()->login($user);

    $company = Company::query()->create([
        'code' => "CMP-PRI-{$suffix}",
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
        'email' => "company-pr-item-{$emailSuffix}@example.test",
        'website' => 'https://example.test',
        'tax_number' => "NPWP-PRI-{$suffix}",
        'is_active' => true,
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => "WH-PRI-{$suffix}",
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
        'code' => "DIV-PRI-{$suffix}",
        'name' => "Division {$suffix}",
        'description' => '',
        'is_active' => true,
    ]);

    $project = Project::query()->create([
        'code' => "PRJ-PRI-{$suffix}",
        'po_code' => "PO-PRI-{$suffix}",
        'name' => "Project {$suffix}",
        'description' => '',
        'allow_po' => true,
        'is_active' => true,
    ]);

    $vendor = Vendor::query()->create([
        'code' => "VND-PRI-{$suffix}",
        'name' => "Vendor {$suffix}",
        'description' => '',
        'address' => "Vendor Address {$suffix}",
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Vendor PIC',
        'contact_person_position' => 'Sales',
        'phone' => '021111111',
        'fax' => '',
        'email' => "vendor-pr-item-{$emailSuffix}@example.test",
        'website' => 'https://example.test',
        'tax_number' => "NPWP-VND-PRI-{$suffix}",
        'is_active' => true,
    ]);

    $category = ItemCategory::query()->create([
        'parent_id' => null,
        'level' => ItemCategory::LEVEL_SUB_CATEGORY,
        'code' => "CAT-PRI-{$suffix}",
        'name' => "Category {$suffix}",
        'description' => '',
        'allow_po' => true,
    ]);

    $item = Item::query()->create([
        'category_id' => $category->id,
        'code' => "ITM-PRI-{$suffix}",
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

function createPurchaseOrderForPurchaseRequestItem(array $context, PurchaseRequest $purchaseRequest, string $suffix, PurchaseOrderStatus $status): PurchaseOrder
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
    $purchaseOrder->purchaseRequests()->sync([$purchaseRequest->id]);

    return $purchaseOrder;
}
