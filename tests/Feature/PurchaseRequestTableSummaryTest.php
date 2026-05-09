<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\Company;
use App\Models\Division;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('aggregates requested and ordered quantities for purchase request summaries', function () {
    $ctx = createPurchaseRequestSummaryContext();

    $purchaseRequest = PurchaseRequest::query()->create([
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Purchase request summary test',
        'memo' => '',
        'boq' => '',
        'notes' => '',
        'info' => '',
    ]);

    $purchaseRequest->update([
        'status' => PurchaseRequestStatus::REVIEWED,
    ]);

    $firstPurchaseRequestItem = $purchaseRequest->purchaseRequestItems()->create([
        'item_id' => $ctx['item']->id,
        'qty' => 10,
        'description' => '',
        'sort' => 1,
    ]);

    $secondPurchaseRequestItem = $purchaseRequest->purchaseRequestItems()->create([
        'item_id' => $ctx['item']->id,
        'qty' => 5,
        'description' => '',
        'sort' => 2,
    ]);

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

    $purchaseOrder->purchaseRequests()->sync([$purchaseRequest->id]);

    $purchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => $firstPurchaseRequestItem->id,
        'item_id' => $ctx['item']->id,
        'qty' => 4,
        'price' => 10000,
        'description' => '',
        'sort' => 1,
    ]);

    $purchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => $secondPurchaseRequestItem->id,
        'item_id' => $ctx['item']->id,
        'qty' => 1,
        'price' => 10000,
        'description' => '',
        'sort' => 2,
    ]);

    $canceledPurchaseOrder = PurchaseOrder::query()->create([
        'vendor_id' => $ctx['vendor']->id,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Canceled purchase order summary test',
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

    $canceledPurchaseOrder->update([
        'status' => PurchaseOrderStatus::CANCELED,
    ]);

    $canceledPurchaseOrder->purchaseRequests()->sync([$purchaseRequest->id]);

    $canceledPurchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => $firstPurchaseRequestItem->id,
        'item_id' => $ctx['item']->id,
        'qty' => 3,
        'price' => 10000,
        'description' => '',
        'sort' => 1,
    ]);

    $summary = PurchaseRequest::query()
        ->withQuantitySummary()
        ->findOrFail($purchaseRequest->id);

    expect($summary->getTotalRequestedQty())->toBe(15.0)
        ->and($summary->getTotalOrderedQty())->toBe(5.0)
        ->and((float) $summary->getAttribute('purchase_request_items_ordered_percentage'))->toBe(33.33)
        ->and($summary->getOrderedPercentage())->toBe(33.33);
});

function createPurchaseRequestSummaryContext(): array
{
    $user = User::factory()->create();
    auth()->login($user);

    $company = Company::query()->create([
        'code' => 'CMP-PR-SUMMARY',
        'name' => 'Test Company PR Summary',
        'description' => '',
        'alias' => 'TCPRS',
        'address' => 'Jl. Test Company PR Summary',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Tester',
        'contact_person_position' => 'Manager',
        'phone' => '021000000',
        'fax' => '',
        'email' => 'company-pr-summary@example.test',
        'website' => 'https://company-pr-summary.test',
        'tax_number' => 'NPWP-COMPANY-PR-SUMMARY',
        'is_active' => true,
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => 'WH-PR-SUMMARY',
        'name' => 'Test Warehouse PR Summary',
        'description' => '',
        'is_active' => true,
    ]);

    $warehouseAddress = WarehouseAddress::query()->create([
        'warehouse_id' => $warehouse->id,
        'address' => 'Jl. Gudang PR Summary',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'phone' => '',
        'fax' => '',
        'as_default' => true,
    ]);

    $division = Division::query()->create([
        'code' => 'DIV-PR-SUMMARY',
        'name' => 'Test Division PR Summary',
        'description' => '',
        'is_active' => true,
    ]);

    $project = Project::query()->create([
        'code' => 'PRJ-PR-SUMMARY',
        'po_code' => 'PR-SUMMARY',
        'name' => 'Test Project PR Summary',
        'description' => '',
        'allow_po' => true,
        'is_active' => true,
    ]);

    $vendor = Vendor::query()->create([
        'code' => 'VND-PR-SUMMARY',
        'name' => 'Test Vendor PR Summary',
        'description' => '',
        'address' => 'Jl. Vendor PR Summary',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Vendor PIC',
        'contact_person_position' => 'Sales',
        'phone' => '021111111',
        'fax' => '',
        'email' => 'vendor-pr-summary@example.test',
        'website' => 'https://vendor-pr-summary.test',
        'tax_number' => 'NPWP-VENDOR-PR-SUMMARY',
        'is_active' => true,
    ]);

    $category = ItemCategory::query()->create([
        'parent_id' => null,
        'level' => ItemCategory::LEVEL_SUB_CATEGORY,
        'code' => 'CAT-PR-SUMMARY',
        'name' => 'Test Category PR Summary',
        'description' => '',
        'allow_po' => true,
    ]);

    $item = Item::query()->create([
        'category_id' => $category->id,
        'code' => 'ITM-PR-SUMMARY',
        'name' => 'Test Item PR Summary',
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
