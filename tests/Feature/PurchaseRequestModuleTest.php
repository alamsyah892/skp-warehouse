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
use App\Models\PurchaseRequestItem;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(LazilyRefreshDatabase::class);

it('generates PR number, defaults status/type/user, and logs initial status with note number', function () {
    $ctx = createPurchaseRequestContext();

    $purchaseRequest = PurchaseRequest::query()->create([
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => null,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => '',
        'memo' => '',
        'boq' => '',
        'notes' => '',
        'info' => '',
    ]);

    expect($purchaseRequest->user_id)->toBe($ctx['user']->id)
        ->and($purchaseRequest->type)->toBe(PurchaseRequest::TYPE_PURCHASE_REQUEST)
        ->and($purchaseRequest->status)->toBe(PurchaseRequestStatus::DRAFT);

    $prefix = sprintf(
        'PR/%s/%s/%s/%s/',
        now()->format('y'),
        now()->format('m'),
        $ctx['project']->po_code,
        $ctx['division']->code,
    );

    expect($purchaseRequest->number)->toStartWith($prefix)
        ->and($purchaseRequest->number)->toMatch('/\/\d{3}$/');

    $log = $purchaseRequest->statusLogs()->latest('id')->firstOrFail();
    expect($log->to_status)->toBe(PurchaseRequestStatus::DRAFT)
        ->and($log->note)->toBe($purchaseRequest->number);
});

it('allows DRAFT -> REQUESTED for role in flow and denies for user without role', function () {
    $ctx = createPurchaseRequestContext();

    $purchaseRequest = PurchaseRequest::query()->create([
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => null,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'info' => '',
    ]);

    $allowed = User::factory()->create();
    $allowed->assignRole(ensureRole(Role::LOGISTIC));

    $denied = User::factory()->create();

    expect($purchaseRequest->canChangeStatusTo(PurchaseRequestStatus::REQUESTED, $allowed))->toBeTrue()
        ->and($purchaseRequest->canChangeStatusTo(PurchaseRequestStatus::REQUESTED, $denied))->toBeFalse();
});

it('hides CANCELED action when PR has PO and none are canceled', function () {
    $ctx = createPurchaseRequestContext();

    $purchaseRequest = PurchaseRequest::query()->create([
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => null,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'info' => '',
        'status' => PurchaseRequestStatus::APPROVED,
    ]);

    $item = $ctx['item'];
    $purchaseRequestItem = $purchaseRequest->purchaseRequestItems()->create([
        'item_id' => $item->id,
        'qty' => 10,
        'description' => '',
        'sort' => 1,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'vendor_id' => $ctx['vendor_id'],
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => null,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'PO for test',
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
        'status' => PurchaseOrderStatus::ORDERED,
    ]);

    $purchaseOrder->purchaseRequests()->sync([$purchaseRequest->id]);

    $purchaseOrder->purchaseOrderItems()->create([
        'purchase_request_item_id' => $purchaseRequestItem->id,
        'item_id' => $item->id,
        'qty' => 5,
        'price' => 10000,
        'description' => '',
        'sort' => 1,
    ]);

    $purchaseRequest->load(['purchaseRequestItems', 'purchaseOrders']);

    expect($purchaseRequest->hasPurchaseOrdersAllNotCanceled())->toBeTrue();
});

it('increments revision number and prepends revision info when watched fields changed', function () {
    $ctx = createPurchaseRequestContext();

    $purchaseRequest = PurchaseRequest::query()->create([
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => null,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'old',
        'memo' => '',
        'boq' => '',
        'info' => "Rev.01 - first change",
        'status' => PurchaseRequestStatus::REQUESTED,
    ]);

    $oldNumber = $purchaseRequest->number;

    $purchaseRequest->number = "{$purchaseRequest->number}-Rev.01";
    $purchaseRequest->save();

    $data = $purchaseRequest->toArray();
    $data['description'] = 'new';
    $data['info'] = 'second change';

    $purchaseRequest->applyRevision($data);

    expect($purchaseRequest->number)->not->toBe($oldNumber)
        ->and($purchaseRequest->number)->toMatch('/-Rev\.\d+$/')
        ->and($data['info'])->toContain('Rev.02 - second change')
        ->and($data['info'])->toContain('Rev.01 - first change');
});

function ensureRole(string $name): Role
{
    return Role::query()->firstOrCreate([
        'name' => $name,
        'guard_name' => 'web',
    ]);
}

function createPurchaseRequestContext(): array
{
    $user = User::factory()->create();
    Auth::login($user);

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

    $vendorId = \App\Models\Vendor::query()->create([
        'code' => 'VND-PR-TEST',
        'name' => 'Test Vendor PR',
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
    ])->id;

    return [
        'user' => $user,
        'company' => $company,
        'warehouse' => $warehouse,
        'division' => $division,
        'project' => $project,
        'item' => $item,
        'vendor_id' => $vendorId,
    ];
}
