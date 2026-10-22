<?php

use App\Enums\PurchaseOrderTaxType;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Filament\Resources\Invoices\Schemas\InvoiceInfolist;
use App\Livewire\InvoiceItemsTable;
use App\Models\GoodsReceive;
use App\Models\GoodsReceiveItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\Vendor;
use App\Services\OrderCalculationService;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

test('invoice resource is configured correctly', function (): void {
    expect(InvoiceResource::getModel())
        ->toBe(Invoice::class)
        ->and(InvoiceResource::getNavigationGroup())
        ->toBe('Finance')
        ->and(InvoiceResource::getNavigationLabel())
        ->toBe('Invoice')
        ->and(InvoiceResource::getGloballySearchableAttributes())
        ->toBe(['number', 'vendor.name', 'goodsReceive.number', 'purchaseOrder.number']);
});

test('invoice summary follows tax discount and rounding rules', function (): void {
    $summary = OrderCalculationService::calculateSummary(
        [
            ['qty' => 2, 'price' => 1000],
            ['qty' => 3, 'price' => 500],
        ],
        discount: 500,
        taxType: PurchaseOrderTaxType::EXCLUDE,
        taxPercentage: 11,
        rounding: 5,
    );

    expect($summary['subtotal'])->toBe(3500.0)
        ->and($summary['discount'])->toBe(500.0)
        ->and($summary['subtotal_after_discount'])->toBe(3000.0)
        ->and($summary['tax'])->toBe(330.0)
        ->and($summary['grand_total'])->toBe(3335.0);
});

test('invoice form summary section matches purchase order structure', function (): void {
    $reflection = new ReflectionClass(InvoiceForm::class);
    $method = $reflection->getMethod('summaryTotalSection');
    $method->setAccessible(true);
    /** @var Section $section */
    $section = $method->invoke(null);

    expect($section->getHeading())->toBe(__('purchase-order.section.summary_total.label'));
    $childComponents = $section->getDefaultChildComponents();
    expect($childComponents)->toHaveCount(2);

    $grid = $childComponents[0];
    $fieldset = $childComponents[1];

    $leftFieldNames = collect($grid->getDefaultChildComponents())->map(fn ($c) => $c->getName())->all();
    expect($leftFieldNames)->toBe([
        'tax_type',
        'tax_percentage',
        'tax_description',
        'discount',
        'rounding',
    ]);

    $rightFieldNames = collect($fieldset->getDefaultChildComponents())
        ->map(fn ($c) => method_exists($c, 'getName') ? $c->getName() : null)
        ->filter()
        ->values()
        ->all();
    expect($rightFieldNames)->toBe([
        'total_subtotal',
        'total_discount',
        'total_after_discount',
        'total_tax_base',
        'total_ppn',
        'total',
        'total_rounding',
        'total_grand_total',
    ]);
});

test('invoice infolist summary section matches purchase order structure', function (): void {
    $reflection = new ReflectionClass(InvoiceInfolist::class);
    $method = $reflection->getMethod('summaryTotalSection');
    $method->setAccessible(true);
    /** @var Section $section */
    $section = $method->invoke(null);

    expect($section->getHeading())->toBe(__('purchase-order.section.summary_total.label'));
    $childComponents = $section->getDefaultChildComponents();
    expect($childComponents)->toHaveCount(2);

    $grid = $childComponents[0];
    $fieldset = $childComponents[1];

    $leftFieldNames = collect($grid->getDefaultChildComponents())->map(fn ($c) => $c->getName())->all();
    expect($leftFieldNames)->toBe([
        'tax_type',
        'tax_percentage',
        'tax_description',
    ]);

    $rightFieldNames = collect($fieldset->getDefaultChildComponents())
        ->map(fn ($c) => method_exists($c, 'getName') ? $c->getName() : null)
        ->filter()
        ->values()
        ->all();
    expect($rightFieldNames)->toBe([
        'total_subtotal',
        'total_discount',
        'total_after_discount',
        'total_dpp',
        'total_ppn',
        'total_before_rounding',
        'summary_rounding',
        'total_grand_total',
    ]);
});

test('invoice infolist calculates summary accurately', function (): void {
    $reflection = new ReflectionClass(InvoiceInfolist::class);
    $method = $reflection->getMethod('getSummary');
    $method->setAccessible(true);

    $invoice = new Invoice([
        'discount' => 200,
        'tax_type' => PurchaseOrderTaxType::EXCLUDE,
        'tax_percentage' => 11,
        'rounding' => 5,
    ]);
    $invoice->id = 999;
    $invoice->setRelation('invoiceItems', collect([
        new InvoiceItem(['id' => 1, 'qty' => 2, 'price' => 1000]),
        new InvoiceItem(['id' => 2, 'qty' => 1, 'price' => 500]),
    ]));

    $summary = $method->invoke(null, $invoice);

    expect($summary['subtotal'])->toBe(2500.0)
        ->and($summary['discount'])->toBe(200.0)
        ->and($summary['subtotal_after_discount'])->toBe(2300.0)
        ->and($summary['tax_base'])->toBe(2300.0)
        ->and($summary['tax_amount'])->toBe(253.0)
        ->and($summary['total_before_rounding'])->toBe(2553.0)
        ->and($summary['rounding'])->toBe(5.0)
        ->and($summary['grand_total'])->toBe(2558.0);
});

test('invoice form applies purchase order summary fields correctly', function (): void {
    $reflection = new ReflectionClass(InvoiceForm::class);
    $method = $reflection->getMethod('applyPurchaseOrderSummaryFields');
    $method->setAccessible(true);

    $state = [];
    $set = function (string $key, mixed $value) use (&$state): void {
        $state[$key] = $value;
    };

    $po = new PurchaseOrder([
        'tax_type' => PurchaseOrderTaxType::INCLUDE,
        'tax_percentage' => 12,
        'tax_description' => 'PPN 12%',
        'discount' => 1500,
        'rounding' => 50,
    ]);

    $method->invoke(null, $set, $po);

    expect($state['tax_type'])->toBe(PurchaseOrderTaxType::INCLUDE->value)
        ->and($state['tax_percentage'])->toBe(12)
        ->and($state['tax_description'])->toBe('PPN 12%')
        ->and($state['discount'])->toBe(1500.0)
        ->and($state['rounding'])->toBe(50.0);
});

test('invoice form resets summary fields on reset or when po is null', function (): void {
    $reflection = new ReflectionClass(InvoiceForm::class);
    $resetMethod = $reflection->getMethod('resetSummaryTotalFields');
    $resetMethod->setAccessible(true);

    $state = [
        'tax_type' => PurchaseOrderTaxType::INCLUDE->value,
        'tax_percentage' => 11,
        'tax_description' => 'PPN',
        'discount' => 100,
        'rounding' => 10,
    ];
    $set = function (string $key, mixed $value) use (&$state): void {
        $state[$key] = $value;
    };

    $resetMethod->invoke(null, $set);

    expect($state['tax_type'])->toBe(PurchaseOrderTaxType::EXCLUDE->value)
        ->and($state['tax_percentage'])->toBe(0)
        ->and($state['tax_description'])->toBeNull()
        ->and($state['discount'])->toBe(0)
        ->and($state['rounding'])->toBe(0);

    $applyMethod = $reflection->getMethod('applyPurchaseOrderSummaryFields');
    $applyMethod->setAccessible(true);

    $state['discount'] = 999;
    $applyMethod->invoke(null, $set, null);

    expect($state['discount'])->toBe(0)
        ->and($state['tax_type'])->toBe(PurchaseOrderTaxType::EXCLUDE->value);
});

test('invoice infolist configure sets up layout matching purchase order structure', function (): void {
    $schema = new Schema;
    $configured = InvoiceInfolist::configure($schema);

    $components = $configured->getComponents();
    expect($components)->toHaveCount(1);

    /** @var Grid $outerGrid */
    $outerGrid = $components[0];
    expect($outerGrid)->toBeInstanceOf(Grid::class);
    expect($outerGrid->getColumns())->toEqual([
        'default' => 1,
        'lg' => 4,
    ]);

    $columns = $outerGrid->getDefaultChildComponents();
    expect($columns)->toHaveCount(2);

    // Left column (span 3)
    $leftColumn = $columns[0];
    expect($leftColumn->getColumnSpan())->toEqual([
        'default' => 1,
        'lg' => 3,
    ]);
    $leftSections = $leftColumn->getDefaultChildComponents();
    expect($leftSections)->toHaveCount(3);
    expect($leftSections[0]->getHeading())->toBe('Informasi Invoice');
    expect($leftSections[1])->toBeInstanceOf(Tabs::class);

    $tabs = $leftSections[1]->getDefaultChildComponents();
    expect($tabs)->toHaveCount(2);
    expect($tabs[0])->toBeInstanceOf(Tab::class);
    expect($tabs[0]->getLabel())->toBe('Item Invoice');
    $tabItems = $tabs[0]->getDefaultChildComponents();
    expect($tabItems)->toHaveCount(1);
    expect($tabItems[0])->toBeInstanceOf(Livewire::class);
    expect($tabItems[0]->getComponent())->toBe(InvoiceItemsTable::class);

    expect($leftSections[2]->getHeading())->toBe(__('purchase-order.section.summary_total.label'));

    // Right column (span 1)
    $rightColumn = $columns[1];
    expect($rightColumn->getColumnSpan())->toEqual([
        'default' => 1,
        'lg' => 1,
    ]);
    $rightSections = $rightColumn->getDefaultChildComponents();
    expect($rightSections)->toHaveCount(4);
    expect($rightSections[0]->getHeading())->toBe(__('purchase-order.section.other_info.label'));
    expect($rightSections[1]->getHeading())->toBe(__('goods-receive.section.main_info.label'));
    expect($rightSections[2]->getHeading())->toBe(__('purchase-order.section.main_info.label'));
    expect($rightSections[3]->getHeading())->toBe(__('vendor.section.main_info.label'));
});

test('invoice items table is configured with expected columns', function (): void {
    $invoice = Invoice::factory()->create();
    $widget = new InvoiceItemsTable;
    $widget->record = $invoice;

    $table = Table::make($widget);
    $configuredTable = $widget->table($table);

    $columnNames = collect($configuredTable->getColumns())->map(fn ($c) => $c->getName())->values()->all();

    expect($columnNames)->toBe([
        'sort',
        'item.code',
        'item.name',
        'item.unit',
        'qty',
        'price',
        'subtotal',
    ]);
});

test('invoice stores goods receive item price subtotal and total amount', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $vendor = Vendor::factory()->create();
    $item = Item::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()
        ->for($vendor)
        ->create([
            'discount' => 500,
            'tax_type' => PurchaseOrderTaxType::EXCLUDE,
            'tax_percentage' => 11,
            'rounding' => 5,
        ]);
    $purchaseOrderItem = PurchaseOrderItem::factory()
        ->for($purchaseOrder)
        ->for($item)
        ->create([
            'qty' => 5,
            'price' => 1000,
        ]);
    $goodsReceive = GoodsReceive::factory()
        ->for($purchaseOrder)
        ->create([
            'company_id' => $purchaseOrder->company_id,
            'warehouse_id' => $purchaseOrder->warehouse_id,
            'warehouse_address_id' => $purchaseOrder->warehouse_address_id,
            'division_id' => $purchaseOrder->division_id,
            'project_id' => $purchaseOrder->project_id,
        ]);
    $goodsReceiveItem = GoodsReceiveItem::factory()
        ->for($goodsReceive)
        ->for($purchaseOrderItem)
        ->for($item)
        ->create([
            'qty' => 3,
        ]);

    $summary = OrderCalculationService::calculateSummary(
        [
            ['qty' => $goodsReceiveItem->qty, 'price' => $purchaseOrderItem->price],
        ],
        discount: 500,
        taxType: PurchaseOrderTaxType::EXCLUDE,
        taxPercentage: 11,
        rounding: 5,
    );

    $invoice = Invoice::factory()
        ->for($vendor)
        ->for($goodsReceive, 'goodsReceive')
        ->for($purchaseOrder, 'purchaseOrder')
        ->create([
            'discount' => 500,
            'tax_type' => PurchaseOrderTaxType::EXCLUDE,
            'tax_percentage' => 11,
            'rounding' => 5,
            'total_amount' => $summary['grand_total'],
        ]);
    $invoiceItem = InvoiceItem::factory()
        ->for($invoice)
        ->for($goodsReceiveItem, 'goodsReceiveItem')
        ->for($item)
        ->create([
            'qty' => $goodsReceiveItem->qty,
            'price' => $purchaseOrderItem->price,
        ]);

    $invoice->refresh();
    $invoiceItem->refresh();

    expect($invoice->vendor->is($vendor))->toBeTrue()
        ->and($invoice->goodsReceive->is($goodsReceive))->toBeTrue()
        ->and($invoice->purchaseOrder->is($purchaseOrder))->toBeTrue()
        ->and($invoice->total_amount)->toBe('2780.00')
        ->and($invoiceItem->subtotal)->toBe(3000.0)
        ->and($invoiceItem->purchaseOrderItem->is($purchaseOrderItem))->toBeTrue();
});
