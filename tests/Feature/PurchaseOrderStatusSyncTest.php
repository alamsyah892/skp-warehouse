<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderTaxType;
use App\Enums\PurchaseRequestStatus;
use App\Models\Company;
use App\Models\Division;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('updates related purchase requests to ordered when purchase order becomes ordered', function () {
    $context = createPurchaseOrderStatusSyncContext();

    $purchaseRequest = PurchaseRequest::query()->create([
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'PR sync test',
        'memo' => '',
        'boq' => '',
        'notes' => '',
        'info' => '',
    ]);

    $purchaseRequest->update([
        'status' => PurchaseRequestStatus::REVIEWED,
    ]);

    $purchaseOrder = PurchaseOrder::query()->create([
        'vendor_id' => $context['vendor']->id,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'PO sync test',
        'delivery_date' => now()->toDateString(),
        'delivery_notes' => '',
        'shipping_cost' => 0,
        'shipping_method' => '',
        'notes' => '',
        'terms' => '',
        'info' => '',
        'discount' => 0,
        'tax_type' => PurchaseOrderTaxType::EXCLUDE,
        'tax_percentage' => PurchaseOrder::DEFAULT_TAX_PERCENTAGE,
        'tax_description' => '',
        'rounding' => 0,
    ]);

    $purchaseOrder->purchaseRequests()->sync([$purchaseRequest->id]);
    $purchaseOrder->changeStatus(PurchaseOrderStatus::ORDERED);

    $purchaseRequest->refresh();

    expect($purchaseRequest->status)->toBe(PurchaseRequestStatus::ORDERED)
        ->and($purchaseRequest->statusLogs()->latest('id')->value('note'))->toBe($purchaseOrder->number);
});

function createPurchaseOrderStatusSyncContext(): array
{
    $user = User::factory()->create();
    auth()->login($user);

    $company = Company::query()->create([
        'code' => 'CMP-PO-SYNC',
        'name' => 'Test Company PO Sync',
        'description' => '',
        'alias' => 'TCPSY',
        'address' => 'Jl. Test Company PO Sync',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Tester',
        'contact_person_position' => 'Manager',
        'phone' => '021000000',
        'fax' => '',
        'email' => 'company-po-sync@example.test',
        'website' => 'https://company-po-sync.test',
        'tax_number' => 'NPWP-COMPANY-PO-SYNC',
        'is_active' => true,
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => 'WH-PO-SYNC',
        'name' => 'Test Warehouse PO Sync',
        'description' => '',
        'is_active' => true,
    ]);

    $user->warehouses()->attach($warehouse);
    $company->warehouses()->attach($warehouse);

    $warehouseAddress = WarehouseAddress::query()->create([
        'warehouse_id' => $warehouse->id,
        'address' => 'Jl. Gudang PO Sync',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'phone' => '',
        'fax' => '',
        'as_default' => true,
    ]);

    $division = Division::query()->create([
        'code' => 'DIV-PO-SYNC',
        'name' => 'Test Division PO Sync',
        'description' => '',
        'is_active' => true,
    ]);

    $project = Project::query()->create([
        'code' => 'PRJ-PO-SYNC',
        'po_code' => 'PO-SYNC',
        'name' => 'Test Project PO Sync',
        'description' => '',
        'allow_po' => true,
        'is_active' => true,
    ]);

    $vendor = Vendor::query()->create([
        'code' => 'VND-PO-SYNC',
        'name' => 'Test Vendor PO Sync',
        'description' => '',
        'address' => 'Jl. Vendor PO Sync',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Vendor PIC',
        'contact_person_position' => 'Sales',
        'phone' => '021111111',
        'fax' => '',
        'email' => 'vendor-po-sync@example.test',
        'website' => 'https://vendor-po-sync.test',
        'tax_number' => 'NPWP-VENDOR-PO-SYNC',
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
    ];
}
