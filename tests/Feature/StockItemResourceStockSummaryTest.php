<?php

use App\Enums\GoodsIssueType;
use App\Enums\GoodsReceiveStatus;
use App\Enums\GoodsReceiveType;
use App\Filament\Resources\StockItems\StockItemResource;
use App\Filament\Resources\StockItems\Support\StockItemMutationData;
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
use Carbon\CarbonImmutable;
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

it('returns zero opening balance and full running mutation history when no period is selected', function () {
    $context = createStockContext('HIST');

    $goodsReceiveMarch = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::MANUAL,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Receive March',
        'delivery_order' => 'DO-HIST-01',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceiveMarch->forceFill([
        'status' => GoodsReceiveStatus::CONFIRMED,
        'created_at' => CarbonImmutable::create(2026, 3, 10, 9, 0, 0),
    ])->saveQuietly();

    $goodsReceiveMarch->goodsReceiveItems()->create([
        'item_id' => $context['item']->id,
        'purchase_order_item_id' => null,
        'qty' => 10,
        'description' => 'Line receive March',
        'sort' => 1,
    ]);

    $goodsIssueMarch = GoodsIssue::query()->create([
        'type' => GoodsIssueType::ISSUE,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Issue March',
        'notes' => '',
        'info' => '',
    ]);

    $goodsIssueMarch->forceFill([
        'created_at' => CarbonImmutable::create(2026, 3, 20, 10, 0, 0),
    ])->saveQuietly();

    $goodsIssueMarch->goodsIssueItems()->create([
        'item_id' => $context['item']->id,
        'qty' => 4,
        'description' => 'Line issue March',
        'sort' => 1,
    ]);

    $goodsReceiveApril = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::MANUAL,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Receive April',
        'delivery_order' => 'DO-HIST-02',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceiveApril->forceFill([
        'status' => GoodsReceiveStatus::CONFIRMED,
        'created_at' => CarbonImmutable::create(2026, 4, 5, 11, 0, 0),
    ])->saveQuietly();

    $goodsReceiveApril->goodsReceiveItems()->create([
        'item_id' => $context['item']->id,
        'purchase_order_item_id' => null,
        'qty' => 3,
        'description' => 'Line receive April',
        'sort' => 1,
    ]);

    $row = StockItemResource::getEloquentQuery()->firstOrFail();
    $summary = StockItemMutationData::getSummary($row);

    expect($summary['opening_balance'])->toBe(0.0)
        ->and($summary['total_received'])->toBe(13.0)
        ->and($summary['total_issued'])->toBe(4.0)
        ->and($summary['ending_balance'])->toBe(9.0)
        ->and(array_column($summary['mutations'], 'balance'))->toBe([10.0, 6.0, 9.0]);
});

it('uses previous month ending balance as opening balance when year and month are selected', function () {
    $context = createStockContext('PERIOD');

    $goodsReceiveMarch = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::MANUAL,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Receive March',
        'delivery_order' => 'DO-PERIOD-01',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceiveMarch->forceFill([
        'status' => GoodsReceiveStatus::CONFIRMED,
        'created_at' => CarbonImmutable::create(2026, 3, 10, 9, 0, 0),
    ])->saveQuietly();

    $goodsReceiveMarch->goodsReceiveItems()->create([
        'item_id' => $context['item']->id,
        'purchase_order_item_id' => null,
        'qty' => 8,
        'description' => 'Line receive March',
        'sort' => 1,
    ]);

    $goodsIssueMarch = GoodsIssue::query()->create([
        'type' => GoodsIssueType::ISSUE,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Issue March',
        'notes' => '',
        'info' => '',
    ]);

    $goodsIssueMarch->forceFill([
        'created_at' => CarbonImmutable::create(2026, 3, 20, 10, 0, 0),
    ])->saveQuietly();

    $goodsIssueMarch->goodsIssueItems()->create([
        'item_id' => $context['item']->id,
        'qty' => 3,
        'description' => 'Line issue March',
        'sort' => 1,
    ]);

    $goodsReceiveApril = GoodsReceive::query()->create([
        'type' => GoodsReceiveType::MANUAL,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Receive April',
        'delivery_order' => 'DO-PERIOD-02',
        'notes' => '',
        'info' => '',
    ]);

    $goodsReceiveApril->forceFill([
        'status' => GoodsReceiveStatus::CONFIRMED,
        'created_at' => CarbonImmutable::create(2026, 4, 5, 11, 0, 0),
    ])->saveQuietly();

    $goodsReceiveApril->goodsReceiveItems()->create([
        'item_id' => $context['item']->id,
        'purchase_order_item_id' => null,
        'qty' => 4,
        'description' => 'Line receive April',
        'sort' => 1,
    ]);

    $goodsIssueApril = GoodsIssue::query()->create([
        'type' => GoodsIssueType::ISSUE,
        'company_id' => $context['company']->id,
        'warehouse_id' => $context['warehouse']->id,
        'warehouse_address_id' => $context['warehouseAddress']->id,
        'division_id' => $context['division']->id,
        'project_id' => $context['project']->id,
        'description' => 'Issue April',
        'notes' => '',
        'info' => '',
    ]);

    $goodsIssueApril->forceFill([
        'created_at' => CarbonImmutable::create(2026, 4, 18, 8, 0, 0),
    ])->saveQuietly();

    $goodsIssueApril->goodsIssueItems()->create([
        'item_id' => $context['item']->id,
        'qty' => 2,
        'description' => 'Line issue April',
        'sort' => 1,
    ]);

    $row = StockItemResource::getEloquentQuery()->firstOrFail();
    $summary = StockItemMutationData::getSummary($row, 2026, 4);

    expect($summary['opening_balance'])->toBe(5.0)
        ->and($summary['total_received'])->toBe(4.0)
        ->and($summary['total_issued'])->toBe(2.0)
        ->and($summary['ending_balance'])->toBe(7.0)
        ->and(array_column($summary['mutations'], 'balance'))->toBe([9.0, 7.0]);
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
