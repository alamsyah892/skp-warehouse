<?php

use App\Filament\Resources\StockItems\Pages\ListStockItems;
use App\Filament\Resources\StockItems\StockItemResource;
use App\Filament\Resources\StockItems\Tables\StockItemsTable;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

test('stock items table has warehouse grouping configured with summaries', function (): void {
    $livewire = new ListStockItems;
    $table = Table::make($livewire);
    $table = StockItemsTable::configure($table);

    $groups = $table->getGroups();
    expect($groups)->toHaveKey('warehouse_name');

    /** @var Group $warehouseGroup */
    $warehouseGroup = $groups['warehouse_name'];
    expect($warehouseGroup->getLabel())->toBe(__('warehouse.model.label'))
        ->and($warehouseGroup->isCollapsible())->toBeTrue()
        ->and($table->getDefaultGroup()?->getId())->toBe('warehouse_name');

    $receivedColumn = $table->getColumn('received_qty');
    $issuedColumn = $table->getColumn('issued_qty');
    $availableColumn = $table->getColumn('available_qty');

    expect($receivedColumn->getSummarizers())->not->toBeEmpty()
        ->and($receivedColumn->getSummarizers()[0])->toBeInstanceOf(Sum::class)
        ->and($issuedColumn->getSummarizers())->not->toBeEmpty()
        ->and($issuedColumn->getSummarizers()[0])->toBeInstanceOf(Sum::class)
        ->and($availableColumn->getSummarizers())->not->toBeEmpty()
        ->and($availableColumn->getSummarizers()[0])->toBeInstanceOf(Sum::class);
});

test('stock items grouped query and scoping execute without errors', function (): void {
    $livewire = new ListStockItems;
    $table = Table::make($livewire);
    $table = StockItemsTable::configure($table);

    /** @var Group $warehouseGroup */
    $warehouseGroup = $table->getGroup('warehouse_name');

    $query = StockItemResource::getEloquentQuery();
    $warehouseGroup->orderQuery($query, 'asc');

    // Scoping query with null and non-null values
    $scopedNull = $warehouseGroup->scopeQueryByKey($query->clone(), null);
    expect($scopedNull->toSql())->toContain('warehouses`.`name` is null');

    $scopedNamed = $warehouseGroup->scopeQueryByKey($query->clone(), 'MAIN WAREHOUSE');
    expect($scopedNamed->toSql())->toContain('warehouses`.`name` = ?');

    // Execution against database without throwing query exceptions
    expect(fn () => $scopedNamed->first())->not->toThrow(Throwable::class);
});
