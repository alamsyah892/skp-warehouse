<?php

use App\Enums\GoodsIssueType;
use App\Enums\GoodsReceiveStatus;
use App\Enums\GoodsReceiveType;
use App\Filament\Resources\StockItems\StockItemResource;
use App\Models\Company;
use App\Models\Division;
use App\Models\GoodsIssue;
use App\Models\GoodsReceive;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('builds stock summary using goods receive minus goods issue', function () {
    $context = createStockContext('A');

    $goodsReceive = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::MANUAL,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Stock receive',
        'delivery_order' => 'DO-STOCK-A',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceive->update([
        'status' => GoodsReceiveStatus::CONFIRMED,
    ]);

    $goodsReceive->goodsReceiveItems()->create([
        'item_id' => $context['item']->id,
        'purchase_order_item_id' => null,
        'qty' => 12,
        'description' => '',
        'sort' => 1,
    ]);

    $goodsIssue = GoodsIssue::query()->create([
        'type' => GoodsIssueType::ISSUE,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Stock issue',
        'notes' => '',
        'info' => '',
    ]);

    $goodsIssue->goodsIssueItems()->create([
        'item_id' => $context['item']->id,
        'qty' => 5,
        'description' => '',
        'sort' => 1,
    ]);

    $row = StockItemResource::getEloquentQuery()->first();

    expect($row)->not->toBeNull()
        ->and((float) $row->received_qty)->toBe(12.0)
        ->and((float) $row->issued_qty)->toBe(5.0)
        ->and((float) $row->available_qty)->toBe(7.0);
});

it('can filter stock summary by warehouse context', function () {
    $contextA = createStockContext('A');
    $contextB = createStockContext('B');

    $goodsReceiveA = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::MANUAL,
        'company_id' => $contextA['company']->id,
        'warehouse_id' => $contextA['warehouse']->id,
        'warehouse_address_id' => $contextA['warehouseAddress']->id,
        'division_id' => $contextA['division']->id,
        'project_id' => $contextA['project']->id,
        'description' => 'Stock receive A',
        'delivery_order' => 'DO-STOCK-A',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceiveA->update(['status' => GoodsReceiveStatus::CONFIRMED]);
    $goodsReceiveA->goodsReceiveItems()->create([
        'item_id' => $contextA['item']->id,
        'purchase_order_item_id' => null,
        'qty' => 9,
        'description' => '',
        'sort' => 1,
    ]);

    $goodsReceiveB = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::MANUAL,
        'company_id' => $contextB['company']->id,
        'warehouse_id' => $contextB['warehouse']->id,
        'warehouse_address_id' => $contextB['warehouseAddress']->id,
        'division_id' => $contextB['division']->id,
        'project_id' => $contextB['project']->id,
        'description' => 'Stock receive B',
        'delivery_order' => 'DO-STOCK-B',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceiveB->update(['status' => GoodsReceiveStatus::CONFIRMED]);
    $goodsReceiveB->goodsReceiveItems()->create([
        'item_id' => $contextB['item']->id,
        'purchase_order_item_id' => null,
        'qty' => 4,
        'description' => '',
        'sort' => 1,
    ]);

    $rows = StockItemResource::getEloquentQuery()
        ->where('goods_receives.warehouse_id', $contextA['warehouse']->id)
        ->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->warehouse_id)->toBe($contextA['warehouse']->id);
});

it('shows negative available quantity for monitoring', function () {
    $context = createStockContext('NEG');

    $goodsReceive = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::MANUAL,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Stock receive NEG',
        'delivery_order' => 'DO-STOCK-NEG',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceive->update(['status' => GoodsReceiveStatus::CONFIRMED]);
    $goodsReceive->goodsReceiveItems()->create([
        'item_id' => $context['item']->id,
        'purchase_order_item_id' => null,
        'qty' => 3,
        'description' => '',
        'sort' => 1,
    ]);

    $goodsIssue = GoodsIssue::query()->create([
        'type' => GoodsIssueType::ISSUE,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Stock issue NEG',
        'notes' => '',
        'info' => '',
    ]);

    $goodsIssue->goodsIssueItems()->create([
        'item_id' => $context['item']->id,
        'qty' => 5,
        'description' => '',
        'sort' => 1,
    ]);

    $row = StockItemResource::getEloquentQuery()->first();

    expect($row)->not->toBeNull()
        ->and((float) $row->available_qty)->toBe(-2.0);
});

it('does not show rows with zero available quantity', function () {
    $context = createStockContext('ZERO');

    $goodsReceive = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::MANUAL,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Stock receive ZERO',
        'delivery_order' => 'DO-STOCK-ZERO',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceive->update(['status' => GoodsReceiveStatus::CONFIRMED]);
    $goodsReceive->goodsReceiveItems()->create([
        'item_id' => $context['item']->id,
        'purchase_order_item_id' => null,
        'qty' => 5,
        'description' => '',
        'sort' => 1,
    ]);

    $goodsIssue = GoodsIssue::query()->create([
        'type' => GoodsIssueType::ISSUE,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Stock issue ZERO',
        'notes' => '',
        'info' => '',
    ]);

    $goodsIssue->goodsIssueItems()->create([
        'item_id' => $context['item']->id,
        'qty' => 5,
        'description' => '',
        'sort' => 1,
    ]);

    $row = StockItemResource::getEloquentQuery()->first();

    expect($row)->toBeNull();
});

function createStockContext(string $suffix): array
{
    $suffix = strtoupper($suffix);
    $emailSuffix = strtolower($suffix);

    $user = User::query()->create([
        'name' => "Stock Tester {$suffix}",
        'email' => "stock-tester-{$emailSuffix}@example.test",
        'password' => 'password',
        'email_verified_at' => now(),
        'avatar_url' => '',
    ]);

    auth()->login($user);

    $company = Company::query()->create([
        'code' => "CMP-STK-{$suffix}",
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
        'email' => "company-{$emailSuffix}@example.test",
        'website' => 'https://example.test',
        'tax_number' => "NPWP-{$suffix}",
        'is_active' => true,
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => "WH-STK-{$suffix}",
        'name' => "Warehouse {$suffix}",
        'description' => '',
        'is_active' => true,
    ]);

    $company->warehouses()->attach($warehouse);
    $user->warehouses()->attach($warehouse);

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
        'code' => "DIV-STK-{$suffix}",
        'name' => "Division {$suffix}",
        'description' => '',
        'is_active' => true,
    ]);

    $division->companies()->attach($company);

    $project = Project::query()->create([
        'code' => "PRJ-STK-{$suffix}",
        'po_code' => "PO-STK-{$suffix}",
        'name' => "Project {$suffix}",
        'description' => '',
        'allow_po' => true,
        'is_active' => true,
    ]);

    $project->companies()->attach($company);
    $project->warehouses()->attach($warehouse);

    $category = ItemCategory::query()->create([
        'parent_id' => null,
        'level' => ItemCategory::LEVEL_SUB_CATEGORY,
        'code' => "CAT-STK-{$suffix}",
        'name' => "Category {$suffix}",
        'description' => '',
        'allow_po' => true,
    ]);

    $item = Item::query()->create([
        'category_id' => $category->id,
        'code' => "ITM-STK-{$suffix}",
        'name' => "Item {$suffix}",
        'description' => '',
        'unit' => 'pcs',
        'type' => Item::TYPE_STOCKABLE,
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
