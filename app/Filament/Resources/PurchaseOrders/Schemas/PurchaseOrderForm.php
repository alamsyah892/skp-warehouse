<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderTaxType;
use App\Enums\PurchaseOrderType;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Role;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Zvizvi\UserFields\Components\UserEntry;

class PurchaseOrderForm
{
    public $record;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    'lg' => 4,
                    // 'xl' => 4,
                    // '2xl' => 4,
                ])
                ->dense()
                ->schema([
                    Grid::make() // left / 1
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                            // 'xl' => 3,
                            // '2xl' => 3,
                        ])
                        ->columns(1)
                        ->schema([
                            static::dataSection(), // 1.1

                            static::itemSection(), // 1.2

                            static::summaryTotalSection(), // 1.3
                        ])
                    ,

                    Grid::make() // right / 2
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 1,
                            // 'xl' => 1,
                            // '2xl' => 1,
                        ])
                        ->columns(1)
                        ->schema([
                            static::infoSection(), // 2.1

                            static::vendorInfoSection(), // 2.2

                            static::purchaseRequestInfoSection(), // 2.3
                        ])
                    ,
                ])
            ,
        ]);
    }

    protected static function dataSection(): Section|string
    {
        return Section::make(__('purchase-order.section.main_info.label'))
            ->icon(Heroicon::ShoppingCart)
            ->iconColor('primary')
            ->compact()
            // ->footer(fn($record) => self::dataSectionFooter($record))
            ->columns([
                'default' => 1,
                'lg' => 12,
            ])
            ->schema([
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 7,
                    ])
                    ->columns([
                        'default' => 3,
                        'lg' => 3,
                    ])
                    ->schema([
                        TextEntry::make('number')
                            ->hiddenLabel()
                            ->icon(Heroicon::Hashtag)
                            ->iconColor('primary')
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->fontFamily(FontFamily::Mono)
                            ->columnSpan([
                                'default' => 2,
                                'lg' => 2,
                            ])
                            ->visibleOn('edit')
                        ,
                        TextEntry::make('type')
                            ->hiddenLabel()
                            ->icon(fn($state) => $state?->icon())
                            ->formatStateUsing(fn($state) => $state?->label())
                            ->size(TextSize::Large)
                            ->color(fn($state) => $state?->color())
                            ->badge()
                            ->visibleOn('edit')
                        ,

                        Select::make('type')
                            ->label(__('purchase-order.type.label'))
                            ->options(PurchaseOrderType::options())
                            ->native(false)
                            ->live()
                            ->required()
                            ->columnSpanFull()
                            ->visibleOn('create')
                        ,
                    ])
                ,
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->columns([
                        'default' => 3,
                        'lg' => 3,
                    ])
                    ->schema([
                        TextEntry::make('status')
                            ->hiddenLabel()
                            ->icon(fn($state) => $state?->icon())
                            ->formatStateUsing(fn($state) => $state?->label())
                            ->size(TextSize::Large)
                            ->color(fn($state) => $state?->color())
                            ->badge()
                            ->columnSpan([
                                'default' => 2,
                                'lg' => 2,
                            ])
                            ->visibleOn('edit')
                        ,
                        TextEntry::make('created_at')
                            ->hiddenLabel()
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary')
                            ->date()
                            ->alignEnd()
                            ->visibleOn('edit')
                        ,
                    ])
                ,
                Section::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 7,
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->compact()
                    ->contained(false)
                    ->schema([
                        Select::make('vendor_id')
                            ->label(__('vendor.model.label'))
                            ->relationship('vendor', 'name', fn($query) => $query->orderBy('name')->orderBy('code'))
                            ->searchable(['name', 'code'])
                            ->preload()
                            ->live()
                            ->required()
                            ->disabled(fn($record): bool|null => $record?->goodsReceives()->exists())
                            ->dehydrated()
                            ->columnSpanFull()
                        ,

                        Section::make(__('purchase-order.fieldset.warehouse_project.label'))
                            // ->icon(Heroicon::HomeModern)
                            // ->iconColor('primary')
                            ->columnSpanFull()
                            ->columns([
                                'default' => 1,
                                'lg' => 2,
                            ])
                            ->compact()
                            ->contained(false)
                            ->schema([
                                Select::make('warehouse_id')
                                    ->label(__('warehouse.model.label'))
                                    ->relationship(
                                        'warehouse',
                                        'name',
                                        fn($query) => $query->when(
                                            auth()->user()->warehouses()->exists(),
                                            fn($q) => $q->whereIn('warehouses.id', auth()->user()->warehouses->pluck('id')),
                                        )->orderBy('name')->orderBy('code'),
                                    )
                                    ->searchable(['name', 'code'])
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($set) {
                                        $set('company_id', null);
                                        $set('division_id', null);
                                        $set('project_id', null);
                                        $set('warehouse_address_id', null);
                                    })
                                    ->required()
                                    ->disabled(fn($get, $operation) => $operation === 'edit' || filled($get('purchaseRequests')))
                                    ->dehydrated()
                                ,
                                Select::make('company_id')
                                    ->label(__('purchase-order.company.label'))
                                    ->relationship(
                                        'company',
                                        'alias',
                                        function ($query, $get) {
                                            $warehouseId = $get('warehouse_id');

                                            $query
                                                ->when($warehouseId, fn($q) => $q->whereHas(
                                                    'warehouses',
                                                    fn($qq) => $qq->where('warehouses.id', $warehouseId),
                                                ))
                                                ->orderBy('alias')
                                                ->orderBy('code')
                                            ;
                                        },
                                    )
                                    ->searchable(['alias', 'code'])
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn($set) => $set('project_id', null))
                                    ->required()
                                    ->disabled(
                                        fn($get, string $operation) =>
                                        $operation === 'edit' || blank($get('warehouse_id')) || filled($get('purchaseRequests'))
                                    )
                                    ->dehydrated()
                                ,
                                Select::make('division_id')
                                    ->label(__('division.model.label'))
                                    ->relationship(
                                        'division',
                                        'name',
                                        function ($query, $get) {
                                            $companyId = $get('company_id');

                                            $query
                                                ->when($companyId, fn($q) => $q->whereHas(
                                                    'companies',
                                                    fn($qq) => $qq->where('companies.id', $companyId),
                                                ))
                                                ->orderBy('name')
                                                ->orderBy('code')
                                            ;
                                        }
                                    )
                                    ->searchable(['name', 'code'])
                                    ->preload()
                                    ->required()
                                    ->disabled(
                                        fn($get, string $operation) =>
                                        $operation === 'edit' || blank($get('company_id')) || filled($get('purchaseRequests'))
                                    )
                                    ->dehydrated()
                                ,
                                Select::make('project_id')
                                    ->label(__('project.model.label'))
                                    ->relationship(
                                        'project',
                                        'name',
                                        function ($query, $get) {
                                            $warehouseId = $get('warehouse_id');
                                            $companyId = $get('company_id');

                                            $query
                                                ->when(
                                                    $companyId && $warehouseId,
                                                    fn($q) => $q->where(function ($qq) use ($companyId, $warehouseId) {
                                                        $qq
                                                            ->whereHas('companies', fn($q2) => $q2->where('companies.id', $companyId))
                                                            ->orWhereHas('warehouses', fn($q2) => $q2->where('warehouses.id', $warehouseId))
                                                        ;
                                                    }),
                                                )
                                                ->when(
                                                    $companyId && blank($warehouseId),
                                                    fn($q) => $q->whereHas('companies', fn($qq) => $qq->where('companies.id', $companyId)),
                                                )
                                                ->when(
                                                    $warehouseId && blank($companyId),
                                                    fn($q) => $q->whereHas('warehouses', fn($qq) => $qq->where('warehouses.id', $warehouseId)),
                                                )
                                                ->where('allow_po', true)
                                                ->orderBy('name')->orderBy('code')
                                            ;
                                        }
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} / {$record->po_code} | {$record->name}")
                                    ->searchable(['name', 'code', 'po_code'])
                                    ->preload()
                                    ->required()
                                    ->disabled(
                                        fn($get, string $operation) =>
                                        $operation === 'edit' || blank($get('warehouse_id')) || blank($get('company_id')) || filled($get('purchaseRequests'))
                                    )
                                    ->dehydrated()
                                ,
                            ])
                        ,

                        Select::make('purchaseRequests')
                            ->label(__('purchase-request.model.plural_label'))
                            ->helperText(__('purchase-order.purchase_requests.helper'))
                            ->relationship(
                                titleAttribute: 'number',
                                modifyQueryUsing: function (Builder $query, $get, ?PurchaseOrder $record): Builder {
                                    $header = array_filter([
                                        'warehouse_id' => $get('warehouse_id'),
                                        'company_id' => $get('company_id'),
                                        'division_id' => $get('division_id'),
                                        'project_id' => $get('project_id'),
                                    ]);

                                    foreach ($header as $field => $value) {
                                        $query->where("purchase_requests.{$field}", $value);
                                    }

                                    $query->when(
                                        auth()->user()->warehouses()->exists(),
                                        fn(Builder $builder) => $builder->whereIn(
                                            'purchase_requests.warehouse_id',
                                            auth()->user()->warehouses->pluck('id'),
                                        ),
                                    );

                                    $selectableStatuses = PurchaseOrder::SELECTABLE_PURCHASE_REQUEST_STATUSES;

                                    $selectedIds = [];
                                    if ($record) {
                                        $selectedIds = $record->purchaseRequests()->pluck('purchase_requests.id')->all();
                                    }

                                    $query->where(function (Builder $scopedQuery) use ($selectableStatuses, $selectedIds): void {
                                        $scopedQuery->whereIn('status', $selectableStatuses);

                                        if ($selectedIds !== []) {
                                            $scopedQuery->orWhereIn('purchase_requests.id', $selectedIds);
                                        }
                                    });

                                    return $query->orderByDesc('purchase_requests.id');
                                },
                            )
                            ->multiple()
                            ->searchable(['number', 'description'])
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get): void {
                                $selection = static::resolvePurchaseRequestSelection((array) $state);

                                if ($selection['ids'] !== PurchaseRequest::normalizeIds((array) $state)) {
                                    $set('purchaseRequests', $selection['ids']);
                                }

                                if ($selection['header']) {
                                    $header = $selection['header'];

                                    $set('warehouse_id', $header['warehouse_id'] ?? null);
                                    $set('company_id', $header['company_id'] ?? null);
                                    $set('division_id', $header['division_id'] ?? null);
                                    $set('project_id', $header['project_id'] ?? null);
                                }
                            })
                            ->dehydrated()
                            ->required()
                            ->disabled(fn($record): bool|null => $record?->goodsReceives()->exists())
                            ->columnSpanFull()
                        ,

                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('purchase-order.description.placeholder'))
                            ->helperText(__('purchase-order.description.helper'))
                            ->autosize()
                            ->live(debounce: 500)
                            ->columnSpanFull()
                        ,
                    ])
                ,
                Section::make(__('purchase-order.fieldset.main_info.label'))
                    // ->icon(Heroicon::ClipboardDocumentList)
                    // ->iconColor('primary')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->columns([
                        'default' => 2,
                    ])
                    ->compact()
                    ->contained(false)
                    ->schema([
                        Select::make('warehouse_address_id')
                            ->label(__('purchase-order.warehouse_address.label'))
                            ->relationship(
                                'warehouseAddress',
                                'address',
                                fn($query, $get) => $query->where('warehouse_id', $get('warehouse_id'))
                            )
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->address} - {$record->city}")
                            ->searchable()
                            ->preload()
                            ->default(null)
                            ->disabled(fn($get) => blank($get('warehouse_id')))
                            ->live()
                            ->columnSpanFull()
                        ,

                        DatePicker::make('delivery_date')
                            ->label(__('purchase-order.delivery_date.label'))
                            ->live()
                        ,
                        TextInput::make('shipping_method')
                            ->label(__('purchase-order.shipping_method.label'))
                            ->placeholder(__('purchase-order.shipping_method.placeholder'))
                            ->helperText(__('purchase-order.shipping_method.helper'))
                            ->live(debounce: 500)
                        ,
                        TextArea::make('delivery_notes')
                            ->label(__('purchase-order.delivery_notes.label'))
                            ->placeholder(__('purchase-order.delivery_notes.placeholder'))
                            ->helperText(__('purchase-order.delivery_notes.helper'))
                            ->live(debounce: 500)
                            ->columnSpanFull()
                        ,
                        TextArea::make('terms')
                            ->placeholder(__('purchase-order.terms.placeholder'))
                            ->helperText(__('purchase-order.terms.helper'))
                            ->live(debounce: 500)
                            ->columnSpanFull()
                            ->visible(fn($operation): bool => $operation !== 'edit' || !static::isLogisticUser())
                        ,
                    ])
                ,
            ])
        ;
    }

    protected static function itemSection(): Section|string
    {
        return Section::make('Form ' . __('purchase-order.section.purchase_order_items.label'))
            ->icon(Heroicon::Cube)
            ->iconColor('primary')
            ->columnSpanFull()
            ->columns(1)
            ->compact()
            ->schema([
                Repeater::make('purchaseOrderItems')
                    ->label(__('purchase-order.purchase_order_items.label'))
                    ->hiddenLabel()
                    ->relationship()
                    ->columns([
                        'lg' => 12,
                    ])
                    ->schema([
                        Select::make('purchase_request_item_id')
                            ->label(__('purchase-order.purchase_request_item.label'))
                            ->options(function ($get): array {
                                $purchaseRequestIds = PurchaseRequest::normalizeIds((array) ($get('../../purchaseRequests') ?? []));

                                return PurchaseRequestItem::getOptions($purchaseRequestIds);
                            })
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search, $get): array {
                                $purchaseRequestIds = PurchaseRequest::normalizeIds((array) ($get('../../purchaseRequests') ?? []));

                                return PurchaseRequestItem::getOptions($purchaseRequestIds, $search);
                            })
                            ->preload()
                            ->hint(function ($get, $record): string {
                                $source = PurchaseRequestItem::findWithDetail((int) ($get('purchase_request_item_id') ?? 0));

                                if ($source) {
                                    $purchaseOrderId = $record?->purchase_order_id;

                                    return __('purchase-order.purchase_order_item.source_item.context_value', [
                                        'request_qty' => number_format((float) $source->qty, 2),
                                        'ordered_qty' => number_format($source->getOrderedQty($purchaseOrderId), 2),
                                        'remaining_qty' => number_format($source->getRemainingQty($purchaseOrderId), 2),
                                    ]);
                                }

                                return '';
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $record): void {
                                if (!$state) {
                                    $set('item_id', null);
                                    $set('qty', null);
                                    $set('price', null);
                                    $set('description', null);
                                    return;
                                }

                                $source = PurchaseRequestItem::findWithDetail((int) $state);

                                if (!$source) {
                                    $set('item_id', null);
                                    $set('qty', null);
                                    $set('price', null);
                                    $set('description', null);
                                    return;
                                }

                                $purchaseOrderId = $record?->purchase_order_id;

                                $set('item_id', $source->item_id);
                                $set('qty', $source->getRemainingQty($purchaseOrderId));
                                // $set('qty', $source->getRemainingQty());
                                $set('description', $source->description);
                            })
                            ->disabled(fn($record): bool => $record?->getReceivedQty() ?? 0 > 0)
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->dehydrated()
                            ->columnSpanFull()
                        ,
                        Grid::make()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 8,
                            ])
                            ->columns(1)
                            ->schema([
                                Select::make('item_id')
                                    ->label(__('item.code.label') . ' | ' . __('item.name.label'))
                                    ->relationship('item', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record): string => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->required()
                                    ->disabled(fn($record, $get): bool => ($record?->getReceivedQty() ?? 0 > 0) || filled($get('purchase_request_item_id')))
                                    ->live()
                                    ->dehydrated()
                                ,
                                Textarea::make('description')
                                    ->label(__('common.description.label'))
                                    ->placeholder(__('purchase-order.purchase_order_item.description.placeholder'))
                                    ->helperText(__('purchase-order.purchase_order_item.description.helper'))
                                    ->autosize()
                                ,
                            ])
                        ,
                        Grid::make()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 4,
                            ])
                            ->columns([
                                'default' => 2,
                                'lg' => 2,
                            ])
                            ->schema([
                                TextInput::make('qty')
                                    ->numeric()
                                    ->placeholder(0.01)
                                    ->hint(fn($get): string|null => Item::query()->whereKey($get('item_id'))->value('unit'))
                                    ->minValue(fn($record): float => $record?->getReceivedQty() > 0 ? (float) $record?->getReceivedQty() : 0.01)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->rule(function ($get, $record) {
                                        return function (string $attribute, $value, $fail) use ($get, $record): void {
                                            $sourceId = (int) $get('purchase_request_item_id');

                                            if ($sourceId <= 0) {
                                                return;
                                            }

                                            $source = PurchaseRequestItem::findWithDetail($sourceId);

                                            if (!$source) {
                                                return;
                                            }

                                            $purchaseOrderId = $record?->purchase_order_id;
                                            $remaining = $source->getRemainingQty($purchaseOrderId);

                                            if ((float) $value > $remaining) {
                                                $fail(__('purchase-order.validation.qty_exceeded', [
                                                    'remaining' => number_format($remaining, 2),
                                                ]));
                                            }
                                        };
                                    })
                                ,
                                TextEntry::make('received_qty')
                                    ->label(__('purchase-order.purchase_order_item.received_qty.label'))
                                    ->state(fn($record): float => $record?->getReceivedQty() ?? 0)
                                    ->numeric()
                                    ->color(fn($record): string => $record?->getReceivedQtyColor() ?? 'gray')
                                ,
                                TextInput::make('price')
                                    ->label(function ($get): string {
                                        return $get('../../tax_type') === PurchaseOrderTaxType::INCLUDE ->value
                                            ? __('purchase-order.purchase_order_item.price.include_label')
                                            : __('purchase-order.purchase_order_item.price.label')
                                        ;
                                    })
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder(0)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->visible(fn($operation): bool => $operation !== 'edit' || !static::isLogisticUser())
                                ,
                                TextEntry::make('subtotal')
                                    ->label(__('purchase-order.subtotal.label'))
                                    ->state(function ($get): string {
                                        $price = $get('price') ?? 0;
                                        $qty = $get('qty') ?? 0;

                                        return $price * $qty;
                                    })
                                    ->numeric()
                                    ->visible(fn($operation): bool => $operation !== 'edit' || !static::isLogisticUser())
                                ,
                            ])
                        ,
                    ])
                    ->collapsible()
                    ->reorderable()
                    ->orderColumn('sort')
                    ->itemLabel('#')
                    ->itemNumbers()
                    ->deleteAction(
                        fn(Action $action) => $action
                            ->requiresConfirmation()
                            ->visible(function (array $arguments, Repeater $component): bool {
                                $itemState = $component->getRawState()[$arguments['item']] ?? [];

                                return static::isItemDeletable(
                                    itemState: is_array($itemState) ? $itemState : [],
                                );
                            }),
                    )
                    ->defaultItems(0)
                    ->minItems(1)
                    ->live()
                    ->partiallyRenderAfterActionsCalled(false)
                ,
            ])
        ;
    }

    protected static function summaryTotalSection(): Section|string
    {
        return Section::make(__('purchase-order.section.summary_total.label'))
            ->icon(Heroicon::Calculator)
            ->iconColor('primary')
            ->columnSpanFull()
            ->columns([
                'default' => 1,
                'lg' => 12
            ])
            ->compact()
            ->visible(fn($operation): bool => $operation !== 'edit' || !static::isLogisticUser())
            ->schema([
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->schema([
                        Select::make('tax_type')
                            ->label(__('purchase-order.tax_type.label'))
                            ->options(PurchaseOrderTaxType::options())
                            ->native(false)
                            ->default(PurchaseOrderTaxType::EXCLUDE->value)
                            ->live()
                            ->afterStateHydrated(fn($component, $state) => $component->state($state ?? PurchaseOrderTaxType::EXCLUDE->value))
                            ->required()
                        ,
                        Select::make('tax_percentage')
                            ->label(__('purchase-order.tax_percentage.label'))
                            ->options(PurchaseOrder::getTaxPercentageOptions())
                            ->native(false)
                            ->live()
                            ->afterStateHydrated(function ($component, $state): void {
                                $component->state(
                                    filled($state) ? (string) ((int) $state + 0) : 0
                                );
                            })
                        ,
                        TextInput::make('tax_description')
                            ->label(__('purchase-order.tax_description.label'))
                            ->placeholder(__('purchase-order.tax_description.placeholder'))
                            ->helperText(__('purchase-order.tax_description.helper'))
                            ->live(debounce: 500)
                            ->columnSpanFull()
                        ,
                        TextInput::make('discount')
                            ->label(__('purchase-order.discount.label'))
                            ->numeric()
                            ->placeholder(0)
                            ->live(debounce: 500)
                            ->dehydrateStateUsing(fn($state) => $state ?? 0)
                            ->columnSpanFull()
                        ,
                        TextInput::make('rounding')
                            ->label(__('purchase-order.rounding.label'))
                            ->numeric()
                            ->placeholder(0)
                            ->live(debounce: 500)
                            ->dehydrateStateUsing(fn($state) => $state ?? 0)
                            ->columnSpanFull()
                        ,
                    ])
                ,
                Fieldset::make(__('purchase-order.fieldset.detail_total.label'))
                    ->dense()
                    ->columns([
                        'default' => 1,
                        'lg' => 1
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 7,
                    ])
                    ->inlineLabel()
                    ->schema([
                        TextEntry::make('total_subtotal')
                            ->label(__('purchase-order.subtotal.label'))
                            ->state(fn($get): string => static::getSummaryTotals($get)['subtotal'] ?? 0.0)
                            ->numeric()
                            ->alignEnd()
                        ,
                        TextEntry::make('total_discount')
                            ->label(__('purchase-order.discount.label'))
                            ->state(fn($get): string => '-' . static::getSummaryTotals($get)['discount'] ?? 0.0)
                            ->numeric()
                            ->color('danger')
                            ->alignEnd()
                        ,
                        TextEntry::make('total_after_discount')
                            ->label(__('purchase-order.after_discount.label'))
                            ->state(fn($get): string => static::getSummaryTotals($get)['subtotal_after_discount'] ?? 0.0)
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->alignEnd()
                        ,

                        View::make('components.divider'),

                        TextEntry::make('total_tax_base')
                            ->label(__('purchase-order.tax_base.label'))
                            ->state(fn($get): string => static::getSummaryTotals($get)['tax_base'] ?? 0.0)
                            ->numeric()
                            ->alignEnd()
                        ,
                        TextEntry::make('total_ppn')
                            ->label(fn($get) => __('purchase-order.tax.label', ['percentage' => filled($get('tax_percentage')) ? "({$get('tax_percentage')}%)" : '']))
                            ->state(fn($get): string => static::getSummaryTotals($get)['tax_amount'] ?? 0.0)
                            ->numeric()
                            ->color('warning')
                            ->alignEnd()
                        ,
                        TextEntry::make('total')
                            ->label(__('purchase-order.total.label'))
                            ->state(fn($get): string => static::getSummaryTotals($get)['total_before_rounding'] ?? 0.0)
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->alignEnd()
                        ,
                        TextEntry::make('total_rounding')
                            ->label(__('purchase-order.rounding.label'))
                            ->state(fn($get): string => $get('rounding') ?? 0)
                            ->numeric()
                            ->alignEnd()
                        ,
                        TextEntry::make('total_grand_total')
                            ->label(__('purchase-order.grand_total.label'))
                            ->state(fn($get): string => static::getSummaryTotals($get)['grand_total'] ?? 0.0)
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->color('primary')
                            ->alignEnd()
                        ,
                    ])
                    ->visible(fn($operation): bool => $operation !== 'edit' || !static::isLogisticUser())
                ,
            ])
        ;
    }

    protected static function infoSection(): Section|string
    {
        return Section::make(__('purchase-order.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->schema([
                Textarea::make('notes')
                    ->label(__('purchase-order.notes.label'))
                    ->placeholder(__('purchase-order.notes.placeholder'))
                    ->helperText(__('purchase-order.notes.helper'))
                    ->autosize()
                ,
                UserEntry::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->color('gray')
                    ->visibleOn('edit')
                ,
                TextEntry::make('updated_at')->date()
                    ->label(__('common.updated_at.label'))
                    ->size(TextSize::Small)
                    ->color('gray')
                    ->visibleOn('edit')
                ,
                TextEntry::make('deleted_at')->date()
                    ->label(__('common.deleted_at.label'))
                    ->size(TextSize::Small)
                    ->color('gray')
                    ->visible(fn($state) => $state != null)
                ,
                Textarea::make('info')
                    ->label(__('purchase-order.info.label'))
                    ->placeholder(__('purchase-order.info.placeholder'))
                    ->helperText(__('purchase-order.info.helper'))
                    ->autosize()
                    ->required(fn($record, $livewire) => $record?->hasWatchedFieldChangesFromState((array) ($livewire->data ?? [])) === true)
                    ->disabled(fn($record, $livewire) => $record?->hasWatchedFieldChangesFromState((array) ($livewire->data ?? [])) !== true)
                    ->afterStateHydrated(fn($component) => $component->state(null))
                    ->afterStateUpdated(function ($set, $record, $livewire): void {
                        if ($record?->hasWatchedFieldChangesFromState((array) ($livewire->data ?? [])) !== true) {
                            $set('info', null);
                        }
                    })
                    ->dehydrated(fn($record, $livewire): bool => $record?->hasWatchedFieldChangesFromState((array) ($livewire->data ?? [])) === true)
                    ->visible(fn($record, $operation) => $operation === 'edit' && !$record?->hasStatus(PurchaseOrderStatus::DRAFT))
                ,
                TextEntry::make('info')
                    ->label(__('purchase-order.revision_history.label'))
                    ->formatStateUsing(fn($state) => collect(explode("\n", $state))->map(fn($line) => "• " . e($line))->implode('<br>'))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->visible(fn($state, $record) => filled($state) && !$record?->hasStatus(PurchaseOrderStatus::DRAFT))
                ,
            ])
        ;
    }

    protected static function vendorInfoSection(): Section|string
    {
        return Section::make(__('vendor.section.main_info.label'))
            ->icon(Heroicon::BuildingStorefront)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->dense()
            ->visible(fn($get) => filled($get('vendor_id')))
            ->schema(function ($get) {
                $vendorId = $get('vendor_id');
                $vendor = Vendor::find($vendorId);

                if (!$vendor) {
                    return [];
                }

                return [
                    TextEntry::make('vendor_name')
                        ->hiddenLabel()
                        ->icon(Heroicon::BuildingStorefront)
                        ->iconColor('primary')
                        ->weight(FontWeight::Bold)
                        ->state($vendor->name)
                    ,
                    TextEntry::make('vendor_address')
                        ->hiddenLabel()
                        ->icon(Heroicon::MapPin)
                        ->iconColor('primary')
                        ->state(collect([$vendor->address, $vendor->city])->filter()->join(' - '))
                        ->size(TextSize::Small)
                        ->color('gray')
                        ->visible(fn($state) => filled($state))
                    ,
                    Grid::make()
                        ->columns([
                            'default' => 2,
                            'lg' => 2,
                        ])
                        ->schema([
                            TextEntry::make('vendor_phone')
                                ->hiddenLabel()
                                ->icon(Heroicon::Phone)
                                ->iconColor('primary')
                                ->state($vendor->phone)
                                ->size(TextSize::Small)
                                ->color('gray')
                                ->visible(fn($state) => filled($state))
                            ,
                            TextEntry::make('vendor_fax')
                                ->hiddenLabel()
                                ->icon(Heroicon::DocumentText)
                                ->iconColor('primary')
                                ->state($vendor->fax)
                                ->size(TextSize::Small)
                                ->color('gray')
                                ->visible(fn($state) => filled($state))
                            ,
                        ])
                    ,
                    TextEntry::make('vendor_contact_person')
                        ->hiddenLabel()
                        ->icon(Heroicon::UserCircle)
                        ->iconColor('primary')
                        ->state($vendor->contact_person)
                        ->size(TextSize::Small)
                        ->color('gray')
                        ->visible(fn($state) => filled($state))
                    ,
                    TextEntry::make('vendor_email')
                        ->hiddenLabel()
                        ->icon(Heroicon::Envelope)
                        ->iconColor('primary')
                        ->state($vendor->email)
                        ->size(TextSize::Small)
                        ->color('gray')
                        ->visible(fn($state) => filled($state))
                    ,
                    TextEntry::make('vendor_website')
                        ->hiddenLabel()
                        ->icon(Heroicon::GlobeAlt)
                        ->iconColor('primary')
                        ->state($vendor->website)
                        ->size(TextSize::Small)
                        ->color('gray')
                        ->visible(fn($state) => filled($state))
                    ,
                ];
            })
        ;
    }

    protected static function purchaseRequestInfoSection(): Section|string
    {
        return Section::make(__('purchase-request.section.main_info.label'))
            ->icon(Heroicon::DocumentText)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->dense()
            ->visible(fn($get) => filled($get('purchaseRequests')))
            ->schema(function ($get) {
                $purchaseRequestIds = PurchaseRequest::normalizeIds((array) ($get('purchaseRequests') ?? []));

                if (blank($purchaseRequestIds)) {
                    return [];
                }

                return PurchaseRequest::query()
                    ->with(['user', 'warehouseAddress'])
                    ->whereIn('id', $purchaseRequestIds)
                    ->get()
                    ->map(function (PurchaseRequest $purchaseRequest) {
                        return Section::make()
                            ->hiddenLabel()
                            ->compact()
                            ->schema([
                                TextEntry::make("purchase_request_{$purchaseRequest->id}_number")
                                    ->hiddenLabel()
                                    ->icon(Heroicon::Hashtag)
                                    ->iconColor('primary')
                                    ->state($purchaseRequest->number)
                                    ->fontFamily(FontFamily::Mono)
                                    ->weight(FontWeight::Bold)
                                    ->url(fn(): string => PurchaseRequestResource::getUrl('view', ['record' => $purchaseRequest->id]))
                                ,
                                Grid::make()
                                    ->columns([
                                        'default' => 2,
                                        'lg' => 2,
                                    ])
                                    ->schema([
                                        TextEntry::make("purchase_request_{$purchaseRequest->id}_status")
                                            ->hiddenLabel()
                                            ->icon($purchaseRequest->status->icon())
                                            ->state($purchaseRequest->status->label())
                                            ->color($purchaseRequest->status->color())
                                            ->badge()
                                        ,
                                        TextEntry::make("purchase_request_{$purchaseRequest->id}_created_at")
                                            ->hiddenLabel()
                                            ->icon(Heroicon::CalendarDays)
                                            ->iconColor('primary')
                                            ->state($purchaseRequest->created_at)
                                            ->date()
                                            ->alignEnd()
                                        ,
                                    ])
                                ,
                                TextEntry::make("purchase_request_{$purchaseRequest->id}_warehouse_address")
                                    ->hiddenLabel()
                                    ->icon(Heroicon::MapPin)
                                    ->iconColor('primary')
                                    ->state($purchaseRequest->warehouseAddress
                                        ? collect([$purchaseRequest->warehouseAddress->address, $purchaseRequest->warehouseAddress->city])->filter()->join(' - ')
                                        : '')
                                    ->html()
                                    ->size(TextSize::Small)
                                    ->color('gray')
                                    ->visible(fn($state) => filled($state))
                                ,
                                TextEntry::make("purchase_request_{$purchaseRequest->id}_description")
                                    ->hiddenLabel()
                                    ->state(nl2br(e($purchaseRequest->description)))
                                    ->html()
                                    ->size(TextSize::Small)
                                    ->color('gray')
                                    ->visible(fn($state) => filled($state))
                                ,

                                UserEntry::make("purchase_request_{$purchaseRequest->id}_user")
                                    ->hiddenLabel()
                                    ->state($purchaseRequest->user)
                                    ->color('gray')
                                ,
                            ])
                        ;
                    })
                    ->toArray()
                ;
            })
        ;
    }



    protected static function resolvePurchaseRequestSelection(array $selectedIds): array
    {
        $selectedIds = PurchaseRequest::normalizeIds($selectedIds);

        if (blank($selectedIds)) {
            return [
                'ids' => [],
                'header' => null,
            ];
        }

        /** @var Collection<int, PurchaseRequest> $purchaseRequests */
        $purchaseRequests = PurchaseRequest::query()
            ->whereIn('id', $selectedIds)
            ->get([
                'id',
                'warehouse_id',
                'company_id',
                'division_id',
                'project_id',
            ])
            ->keyBy('id')
        ;

        $firstPurchaseRequest = $purchaseRequests->get($selectedIds[0]);

        if (!$firstPurchaseRequest) {
            return [
                'ids' => [],
                'header' => null,
            ];
        }

        $compatibleIds = collect($selectedIds)
            ->filter(fn(int $id): bool => static::headersMatch(
                $purchaseRequests->get($id),
                $firstPurchaseRequest,
            ))
            ->values()
            ->all()
        ;

        return [
            'ids' => $compatibleIds,
            'header' => [
                'warehouse_id' => $firstPurchaseRequest->warehouse_id,
                'company_id' => $firstPurchaseRequest->company_id,
                'division_id' => $firstPurchaseRequest->division_id,
                'project_id' => $firstPurchaseRequest->project_id,
            ],
        ];
    }

    protected static function headersMatch(?PurchaseRequest $current, PurchaseRequest $first): bool
    {
        if (!$current) {
            return false;
        }

        return $current->warehouse_id === $first->warehouse_id
            && $current->company_id === $first->company_id
            && $current->division_id === $first->division_id
            && $current->project_id === $first->project_id
        ;
    }


    protected static function getSummaryTotals(
        callable $get,
        string $itemsPath = 'purchaseOrderItems',
        string $basePath = '',
    ): array {
        $resolvePath = static fn(string $field) => $basePath !== ''
            ? "{$basePath}{$field}"
            : $field;
        $items = PurchaseOrder::normalizeSummaryItems((array) ($get($itemsPath) ?? []));
        $discount = (float) ($get($resolvePath('discount')) ?? 0);
        $taxType = $get($resolvePath('tax_type'));
        $taxPercentage = ($get($resolvePath('tax_percentage')) ?? 0);
        $rounding = (float) ($get($resolvePath('rounding')) ?? 0);

        return PurchaseOrder::calculateOrderSummary(
            $items,
            $discount,
            $taxType,
            $taxPercentage,
            $rounding,
        );
    }

    protected static function isItemDeletable(array $itemState = []): bool
    {
        $itemId = $itemState['id'] ?? null;

        if (blank($itemId)) {
            return true;
        }

        $item = PurchaseOrderItem::query()->find($itemId);

        if (!$item) {
            return true;
        }

        return $item->getReceivedQty() == 0;
    }

    protected static function isLogisticUser(): bool
    {
        return auth()->user()?->hasAnyRole([Role::LOGISTIC, Role::LOGISTIC_MANAGER]) === true;
    }
}
