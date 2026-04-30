<?php

use App\Enums\GoodsIssueStatus;
use App\Enums\GoodsIssueType;
use App\Models\Company;
use App\Models\Division;
use App\Models\GoodsIssue;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('aggregates issued quantities for goods issue summaries', function () {
    $ctx = createGoodsIssueSummaryContext();

    $goodsIssue = GoodsIssue::query()->create([
        'type' => GoodsIssueType::ISSUE,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Goods issue summary test',
        'notes' => '',
        'info' => '',
    ]);

    $goodsIssue->goodsIssueItems()->create([
        'item_id' => $ctx['item']->id,
        'qty' => 2,
        'description' => '',
        'sort' => 1,
    ]);

    $goodsIssue->goodsIssueItems()->create([
        'item_id' => $ctx['item']->id,
        'qty' => 3,
        'description' => '',
        'sort' => 2,
    ]);

    $canceledGoodsIssue = GoodsIssue::query()->create([
        'type' => GoodsIssueType::TRANSFER,
        'company_id' => $ctx['company']->id,
        'warehouse_id' => $ctx['warehouse']->id,
        'warehouse_address_id' => $ctx['warehouseAddress']->id,
        'division_id' => $ctx['division']->id,
        'project_id' => $ctx['project']->id,
        'description' => 'Canceled goods issue summary test',
        'notes' => '',
        'info' => '',
    ]);

    $canceledGoodsIssue->update([
        'status' => GoodsIssueStatus::CANCELED,
    ]);

    $canceledGoodsIssue->goodsIssueItems()->create([
        'item_id' => $ctx['item']->id,
        'qty' => 9,
        'description' => '',
        'sort' => 1,
    ]);

    $summary = GoodsIssue::query()
        ->withQuantitySummary()
        ->findOrFail($goodsIssue->id);

    expect((float) $summary->getAttribute('goods_issue_items_sum_qty'))->toBe(5.0)
        ->and($summary->getTotalIssuedQty())->toBe(5.0);
});

function createGoodsIssueSummaryContext(): array
{
    $user = User::query()->create([
        'name' => 'Goods Issue Summary Tester',
        'email' => 'goods-issue-summary-tester@example.test',
        'password' => 'password',
        'email_verified_at' => now(),
        'avatar_url' => '',
    ]);

    auth()->login($user);

    $company = Company::query()->create([
        'code' => 'CMP-GI-SUMMARY',
        'name' => 'Test Company GI Summary',
        'description' => '',
        'alias' => 'TCGISU',
        'address' => 'Jl. Test Company GI Summary',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'contact_person' => 'Tester',
        'contact_person_position' => 'Manager',
        'phone' => '021000000',
        'fax' => '',
        'email' => 'company-gi-summary@example.test',
        'website' => 'https://company-gi-summary.test',
        'tax_number' => 'NPWP-COMPANY-GI-SUMMARY',
        'is_active' => true,
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => 'WH-GI-SUMMARY',
        'name' => 'Test Warehouse GI Summary',
        'description' => '',
        'is_active' => true,
    ]);

    $company->warehouses()->attach($warehouse);
    $user->warehouses()->attach($warehouse);

    $warehouseAddress = WarehouseAddress::query()->create([
        'warehouse_id' => $warehouse->id,
        'address' => 'Jl. Gudang GI Summary',
        'city' => 'Jakarta',
        'post_code' => '10110',
        'phone' => '',
        'fax' => '',
        'as_default' => true,
    ]);

    $division = Division::query()->create([
        'code' => 'DIV-GI-SUMMARY',
        'name' => 'Test Division GI Summary',
        'description' => '',
        'is_active' => true,
    ]);

    $division->companies()->attach($company);

    $project = Project::query()->create([
        'code' => 'PRJ-GI-SUMMARY',
        'po_code' => 'GI-SUMMARY',
        'name' => 'Test Project GI Summary',
        'description' => '',
        'allow_po' => true,
        'is_active' => true,
    ]);

    $project->companies()->attach($company);
    $project->warehouses()->attach($warehouse);

    $category = ItemCategory::query()->create([
        'parent_id' => null,
        'level' => ItemCategory::LEVEL_SUB_CATEGORY,
        'code' => 'CAT-GI-SUMMARY',
        'name' => 'Test Category GI Summary',
        'description' => '',
        'allow_po' => true,
    ]);

    $item = Item::query()->create([
        'category_id' => $category->id,
        'code' => 'ITM-GI-SUMMARY',
        'name' => 'Test Item GI Summary',
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
