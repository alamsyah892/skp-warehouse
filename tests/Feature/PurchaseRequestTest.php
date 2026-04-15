<?php

use App\Enums\PurchaseOrderTaxType;
use App\Enums\PurchaseRequestStatus;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestInfolist;
use App\Livewire\PurchaseRequestPurchaseOrdersTable;
use App\Models\Company;
use App\Models\Division;
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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

it('hides canceled purchase request action when any item has ordered quantity', function () {
    $purchaseRequest = createPurchaseRequestForInfolist();
    $purchaseRequest->update([
        'status' => PurchaseRequestStatus::APPROVED,
    ]);

    $purchaseRequestItem = $purchaseRequest->purchaseRequestItems()->firstOrFail();

    $vendor = Vendor::query()->create([
        'code' => 'VND-PR-TEST',
        'name' => 'Vendor PR Test',
        'description' => '',
        'address' => 'Jl. Vendor PR',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Vendor PIC',
        'contact_person_position' => 'Sales',
        'phone' => '021111111',
        'fax' => '',
        'email' => 'vendor-pr@example.test',
        'website' => 'https://vendor-pr.test',
        'tax_number' => 'NPWP-VENDOR-PR',
        'is_active' => true,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'vendor_id' => $vendor->id,
        'company_id' => $purchaseRequest->company_id,
        'warehouse_id' => $purchaseRequest->warehouse_id,
        'warehouse_address_id' => $purchaseRequest->warehouse_address_id,
        'division_id' => $purchaseRequest->division_id,
        'project_id' => $purchaseRequest->project_id,
        'description' => 'Purchase order for PR test',
        'delivery_date' => now()->toDateString(),
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

    $purchaseOrder->purchaseRequests()->sync([$purchaseRequest->id]);
    $purchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => $purchaseRequestItem->id,
        'item_id' => $purchaseRequestItem->item_id,
        'qty' => 1,
        'price' => 10000,
        'description' => 'Ordered from PR',
        'sort' => 1,
    ]);

    $purchaseRequest->refresh()->load('purchaseRequestItems');

    expect(PurchaseRequestInfolist::shouldHideStatusAction($purchaseRequest, PurchaseRequestStatus::CANCELED))
        ->toBeTrue()
        ->and(PurchaseRequestInfolist::shouldHideStatusAction($purchaseRequest, PurchaseRequestStatus::ORDERED))
        ->toBeTrue();
});

it('keeps canceled purchase request action visible when ordered quantity is zero', function () {
    $purchaseRequest = createPurchaseRequestForInfolist();
    $purchaseRequest->update([
        'status' => PurchaseRequestStatus::APPROVED,
    ]);

    $purchaseRequest->refresh()->load('purchaseRequestItems');

    expect(PurchaseRequestInfolist::shouldHideStatusAction($purchaseRequest, PurchaseRequestStatus::CANCELED))
        ->toBeFalse();
});

it('hides finished purchase request action when any item still has remaining quantity', function () {
    $purchaseRequest = createPurchaseRequestForInfolist();
    $purchaseRequest->update([
        'status' => PurchaseRequestStatus::ORDERED,
    ]);

    $purchaseRequest->refresh()->load('purchaseRequestItems');

    expect(PurchaseRequestInfolist::shouldHideStatusAction($purchaseRequest, PurchaseRequestStatus::FINISHED))
        ->toBeTrue();
});

it('keeps finished purchase request action visible when all items are fully ordered', function () {
    $purchaseRequest = createPurchaseRequestForInfolist();
    $purchaseRequest->update([
        'status' => PurchaseRequestStatus::ORDERED,
    ]);

    $purchaseRequestItem = $purchaseRequest->purchaseRequestItems()->firstOrFail();

    createPurchaseOrderForPurchaseRequest($purchaseRequest, $purchaseRequestItem, 10);

    $purchaseRequest->refresh()->load('purchaseRequestItems');

    expect(PurchaseRequestInfolist::shouldHideStatusAction($purchaseRequest, PurchaseRequestStatus::FINISHED))
        ->toBeFalse();
});

it('prevents deleting purchase request items that already have ordered quantity', function () {
    $purchaseRequest = createPurchaseRequestForInfolist();
    $purchaseRequestItem = $purchaseRequest->purchaseRequestItems()->firstOrFail();

    createPurchaseOrderForPurchaseRequest($purchaseRequest, $purchaseRequestItem, 1);

    expect(PurchaseRequestForm::isPurchaseRequestItemDeletable([
        'id' => $purchaseRequestItem->id,
    ]))->toBeFalse();
});

it('allows deleting purchase request items that do not have ordered quantity', function () {
    $purchaseRequest = createPurchaseRequestForInfolist();
    $purchaseRequestItem = $purchaseRequest->purchaseRequestItems()->firstOrFail();

    expect(PurchaseRequestForm::isPurchaseRequestItemDeletable([
        'id' => $purchaseRequestItem->id,
    ]))->toBeTrue()
        ->and(PurchaseRequestForm::isPurchaseRequestItemDeletable())->toBeTrue();
});

it('renders related purchase orders in the purchase request purchase orders table', function () {
    $purchaseRequest = createPurchaseRequestForInfolist();
    $purchaseRequestItem = $purchaseRequest->purchaseRequestItems()->firstOrFail();

    $purchaseOrder = createPurchaseOrderForPurchaseRequest($purchaseRequest, $purchaseRequestItem, 1);

    Livewire::test(PurchaseRequestPurchaseOrdersTable::class, ['record' => $purchaseRequest])
        ->call('loadTable')
        ->assertSee($purchaseOrder->number)
        ->assertSee('Vendor PR Test');
});

function createPurchaseRequestForInfolist(): PurchaseRequest
{
    $user = User::factory()->create();

    auth()->login($user);

    $company = Company::query()->create([
        'code' => 'CMP-PR-TEST',
        'name' => 'Test Company PR',
        'description' => '',
        'alias' => 'TCPR',
        'address' => 'Jl. Test Company PR',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Tester',
        'contact_person_position' => 'Manager',
        'phone' => '021000000',
        'fax' => '',
        'email' => 'company-pr@example.test',
        'website' => 'https://company-pr.test',
        'tax_number' => 'NPWP-COMPANY-PR',
        'is_active' => true,
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => 'WH-PR-TEST',
        'name' => 'Test Warehouse PR',
        'description' => '',
        'is_active' => true,
    ]);

    $warehouseAddress = WarehouseAddress::query()->create([
        'warehouse_id' => $warehouse->id,
        'address' => 'Jl. Gudang PR',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'phone' => '',
        'fax' => '',
        'as_default' => true,
    ]);

    $division = Division::query()->create([
        'code' => 'DIV-PR-TEST',
        'name' => 'Test Division PR',
        'description' => '',
        'is_active' => true,
    ]);

    $project = Project::query()->create([
        'code' => 'PRJ-PR-TEST',
        'po_code' => 'PO-PR-TEST',
        'name' => 'Test Project PR',
        'description' => '',
        'allow_po' => true,
        'is_active' => true,
    ]);

    $category = ItemCategory::query()->create([
        'parent_id' => null,
        'level' => ItemCategory::LEVEL_SUB_CATEGORY,
        'code' => 'CAT-PR-TEST',
        'name' => 'Test Category PR',
        'description' => '',
        'allow_po' => true,
    ]);

    $item = Item::query()->create([
        'category_id' => $category->id,
        'code' => 'ITM-PR-TEST',
        'name' => 'Test Item PR',
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
        'description' => 'Purchase request for infolist test',
        'memo' => '',
        'boq' => '',
        'notes' => '',
        'info' => '',
    ]);

    $purchaseRequest->purchaseRequestItems()->create([
        'item_id' => $item->id,
        'qty' => 10,
        'description' => 'Requested item',
        'sort' => 1,
    ]);

    return $purchaseRequest->fresh(['purchaseRequestItems']);
}

function createPurchaseOrderForPurchaseRequest(
    PurchaseRequest $purchaseRequest,
    PurchaseRequestItem $purchaseRequestItem,
    float $qty
): PurchaseOrder {
    $vendor = Vendor::query()->create([
        'code' => 'VND-PR-TEST-' . str()->random(6),
        'name' => 'Vendor PR Test',
        'description' => '',
        'address' => 'Jl. Vendor PR',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Vendor PIC',
        'contact_person_position' => 'Sales',
        'phone' => '021111111',
        'fax' => '',
        'email' => 'vendor-pr-' . str()->lower(str()->random(6)) . '@example.test',
        'website' => 'https://vendor-pr.test',
        'tax_number' => 'NPWP-VENDOR-PR-' . str()->upper(str()->random(4)),
        'is_active' => true,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'vendor_id' => $vendor->id,
        'company_id' => $purchaseRequest->company_id,
        'warehouse_id' => $purchaseRequest->warehouse_id,
        'warehouse_address_id' => $purchaseRequest->warehouse_address_id,
        'division_id' => $purchaseRequest->division_id,
        'project_id' => $purchaseRequest->project_id,
        'description' => 'Purchase order for PR test',
        'delivery_date' => now()->toDateString(),
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

    $purchaseOrder->purchaseRequests()->sync([$purchaseRequest->id]);
    $purchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => $purchaseRequestItem->id,
        'item_id' => $purchaseRequestItem->item_id,
        'qty' => $qty,
        'price' => 10000,
        'description' => 'Ordered from PR',
        'sort' => 1,
    ]);

    return $purchaseOrder;
}
