<?php

use App\Enums\GoodsIssueStatus;
use App\Enums\GoodsIssueType;
use App\Enums\GoodsReceiveStatus;
use App\Enums\GoodsReceiveType;
use App\Models\Company;
use App\Models\Division;
use App\Models\GoodsIssue;
use App\Models\GoodsIssueItem;
use App\Models\GoodsReceive;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('calculates available goods issue quantity from received and confirmed stock minus issued stock', function () {
    $ctx = createGoodsIssueContext();

    $goodsReceive = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::MANUAL,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Goods receive for goods issue stock',
        'delivery_order' => 'DO-GI-STOCK-001',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceive->update([
        'status' => GoodsReceiveStatus::CONFIRMED,
    ]);

    $goodsReceive->goodsReceiveItems()->create([
        'item_id' => $ctx['item']->id,
        'purchase_order_item_id' => null,
        'qty' => 10,
        'description' => '',
        'sort' => 1,
    ]);

    $issuedGoodsIssue = GoodsIssue::query()->create([
        'type' => GoodsIssueType::ISSUE,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Issued stock',
        'notes' => '',
        'info' => '',
    ]);

    $issuedGoodsIssue->goodsIssueItems()->create([
        'item_id' => $ctx['item']->id,
        'qty' => 4,
        'description' => '',
        'sort' => 1,
    ]);

    $canceledGoodsIssue = GoodsIssue::query()->create([
        'type' => GoodsIssueType::ISSUE,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Canceled issue should not reduce stock',
        'notes' => '',
        'info' => '',
    ]);

    $canceledGoodsIssue->update([
        'status' => GoodsIssueStatus::CANCELED,
    ]);

    $canceledGoodsIssue->goodsIssueItems()->create([
        'item_id' => $ctx['item']->id,
        'qty' => 3,
        'description' => '',
        'sort' => 1,
    ]);

    $header = [
        'warehouse_id' => $ctx['warehouse']->id,
        'company_id' => $ctx['company']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
    ];

    $availableQty = GoodsIssueItem::getAvailableQtyForItem($header, $ctx['item']->id);
    $availableQtyWhileEditingIssuedDocument = GoodsIssueItem::getAvailableQtyForItem($header, $ctx['item']->id, $issuedGoodsIssue->id);
    $options = GoodsIssueItem::getSelectableOptions($header);

    expect($availableQty)->toBe(6.0)
        ->and($availableQtyWhileEditingIssuedDocument)->toBe(10.0)
        ->and($options)->toHaveKey($ctx['item']->id)
        ->and($options[$ctx['item']->id])->toContain($ctx['item']->code);
});

function createGoodsIssueContext(): array
{
    $user = User::query()->create([
        'name' => 'Goods Issue Stock Tester',
        'email' => 'goods-issue-stock-tester@example.test',
        'password' => 'password',
        'email_verified_at' => now(),
        'avatar_url' => '',
    ]);

    auth()->login($user);

    $company = Company::query()->create([
        'code' => 'CMP-GI-STOCK',
        'name' => 'Test Company GI Stock',
        'description' => '',
        'alias' => 'TCGIS',
        'address' => 'Jl. Test Company GI Stock',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Tester',
        'contact_person_position' => 'Manager',
        'phone' => '021000000',
        'fax' => '',
        'email' => 'company-gi-stock@example.test',
        'website' => 'https://company-gi-stock.test',
        'tax_number' => 'NPWP-COMPANY-GI-STOCK',
        'is_active' => true,
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => 'WH-GI-STOCK',
        'name' => 'Test Warehouse GI Stock',
        'description' => '',
        'is_active' => true,
    ]);

    $company->warehouses()->attach($warehouse);
    $user->warehouses()->attach($warehouse);

    $warehouseAddress = WarehouseAddress::query()->create([
        'warehouse_id' => $warehouse->id,
        'address' => 'Jl. Gudang GI Stock',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'phone' => '',
        'fax' => '',
        'as_default' => true,
    ]);

    $division = Division::query()->create([
        'code' => 'DIV-GI-STOCK',
        'name' => 'Test Division GI Stock',
        'description' => '',
        'is_active' => true,
    ]);

    $division->companies()->attach($company);

    $project = Project::query()->create([
        'code' => 'PRJ-GI-STOCK',
        'po_code' => 'GI-STOCK',
        'name' => 'Test Project GI Stock',
        'description' => '',
        'allow_po' => true,
        'is_active' => true,
    ]);

    $project->companies()->attach($company);
    $project->warehouses()->attach($warehouse);

    $category = ItemCategory::query()->create([
        'parent_id' => null,
        'level' => ItemCategory::LEVEL_SUB_CATEGORY,
        'code' => 'CAT-GI-STOCK',
        'name' => 'Test Category GI Stock',
        'description' => '',
        'allow_po' => true,
    ]);

    $item = Item::query()->create([
        'category_id' => $category->id,
        'code' => 'ITM-GI-STOCK',
        'name' => 'Test Item GI Stock',
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
        'item' => $item,
    ];
}
