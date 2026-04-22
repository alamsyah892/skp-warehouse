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
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
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
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Zvizvi\UserFields\Components\UserEntry;

class PurchaseOrderForm
{
    public $record;

    public static function getReceivedQtyColumnColor(?PurchaseOrderItem $purchaseOrderItem): string
    {
        if (!$purchaseOrderItem) {
            return 'gray';
        }

        $receivedQty = $purchaseOrderItem->getReceivedQty();
        $orderedQty = (float) $purchaseOrderItem->qty;

        return match (true) {
            $receivedQty == 0 => 'danger',
            $receivedQty < $orderedQty => 'info',
            default => 'success',
        };
    }

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

                            // static::relatedDataSection(), // 2.2
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
                            ->fontFamily(FontFamily::Mono)
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
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
                            ->required()
                            ->live()
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
                                    ->disabled(fn($get, $operation) => $operation === 'edit' || filled($get('purchaseRequests')))
                                    ->relationship(
                                        name: 'warehouse',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn($query) => $query->when(
                                            auth()->user()->warehouses()->exists(),
                                            fn($q) => $q->whereIn('warehouses.id', auth()->user()->warehouses->pluck('id'))
                                        )->orderBy('name')->orderBy('code')
                                    )
                                    ->searchable(['name', 'code'])
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($set) {
                                        $set('company_id', null);
                                        $set('division_id', null);
                                        $set('project_id', null);
                                        $set('warehouse_address_id', null);
                                    })
                                    ->dehydrated()
                                ,
                                Select::make('company_id')
                                    ->label(__('purchase-request.company.label'))
                                    ->disabled(
                                        fn($get, $operation) =>
                                        $operation === 'edit' || blank($get('warehouse_id')) || filled($get('purchaseRequests'))
                                    )
                                    ->relationship(
                                        name: 'company',
                                        titleAttribute: 'alias',
                                        modifyQueryUsing: fn($query) => $query->orderBy('alias')->orderBy('code'),
                                    )
                                    ->searchable(['name', 'alias', 'code'])
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn($set) => $set('project_id', null))
                                    ->dehydrated()
                                ,
                                Select::make('division_id')
                                    ->label(__('division.model.label'))
                                    ->disabled(
                                        fn($get, $operation) =>
                                        $operation === 'edit' || blank($get('company_id')) || filled($get('purchaseRequests'))
                                    )
                                    ->relationship(
                                        name: 'division',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: function ($query, $get) {
                                            $companyId = $get('company_id');

                                            $query
                                                ->when($companyId, function ($q) use ($companyId) {
                                                    $q
                                                        ->whereHas('companies', fn($qq) => $qq->where('companies.id', $companyId))
                                                        ->orWhereDoesntHave('companies')
                                                    ;
                                                })
                                                ->orderBy('name')->orderBy('code')
                                            ;
                                        }
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->dehydrated()
                                ,
                                Select::make('project_id')
                                    ->label(__('project.model.label'))
                                    ->disabled(
                                        fn($get, $operation) =>
                                        $operation === 'edit' ||
                                        blank($get('warehouse_id')) ||
                                        blank($get('company_id')) ||
                                        filled($get('purchaseRequests'))
                                    )
                                    ->relationship(
                                        name: 'project',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: function ($query, $get) {
                                            $warehouseId = $get('warehouse_id');
                                            $companyId = $get('company_id');

                                            $query
                                                ->when($companyId, function ($q) use ($companyId) {
                                                    $q->where(function ($qq) use ($companyId) {
                                                        $qq
                                                            ->whereHas('companies', fn($q) => $q->where('companies.id', $companyId))
                                                            ->orWhereDoesntHave('companies')
                                                        ;
                                                    });
                                                })
                                                ->when($warehouseId, function ($q) use ($warehouseId) {
                                                    $q->where(function ($qq) use ($warehouseId) {
                                                        $qq
                                                            ->whereHas('warehouses', fn($q) => $q->where('warehouses.id', $warehouseId))
                                                            ->orWhereDoesntHave('warehouses')
                                                        ;
                                                    });
                                                })
                                                ->where('allow_po', true)
                                                ->orderBy('name')->orderBy('code')
                                            ;
                                        }
                                    )
                                    ->searchable(['name', 'code', 'po_code'])
                                    ->preload()
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} / {$record->po_code} | {$record->name}")
                                    ->required()
                                    ->live()
                                    ->dehydrated()
                                ,
                            ])
                        ,
                        Select::make('purchaseRequests')
                            ->label(__('purchase-request.model.plural_label'))
                            ->helperText(__('purchase-order.purchase_requests.helper'))
                            ->multiple()
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
                            ->searchable(['number', 'description'])
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get): void {
                                $selection = static::resolvePurchaseRequestSelection((array) $state);

                                if ($selection['ids'] !== PurchaseOrder::normalizePurchaseRequestIds((array) $state)) {
                                    $set('purchaseRequests', $selection['ids']);
                                }

                                if ($selection['header']) {
                                    static::fillHeaderFields($set, $selection['header']);
                                }
                                static::syncPurchaseRequestStatusSnapshot($set, $selection['ids']);
                                static::prunePurchaseOrderItems($set, $get, $selection['ids']);
                            })
                            ->afterStateHydrated(function ($state, $set, $get): void {
                                $selection = static::resolvePurchaseRequestSelection((array) $state);

                                if ($selection['ids'] !== PurchaseOrder::normalizePurchaseRequestIds((array) $state)) {
                                    $set('purchaseRequests', $selection['ids']);
                                }

                                if ($selection['header']) {
                                    static::fillHeaderFields($set, $selection['header']);
                                }
                                if (blank($get('purchaseRequestStatusSnapshot'))) {
                                    static::syncPurchaseRequestStatusSnapshot($set, $selection['ids']);
                                }
                                static::prunePurchaseOrderItems($set, $get, $selection['ids']);
                            })
                            ->columnSpanFull()
                        ,
                        Hidden::make('purchaseRequestStatusSnapshot')
                            ->default([])
                            ->dehydrated()
                        ,
                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('purchase-order.description.placeholder'))
                            ->helperText(__('purchase-order.description.helper'))
                            ->autosize()
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
                            ->label(__('purchase-request.warehouse_address.label'))
                            ->disabled(fn($get) => blank($get('warehouse_id')))
                            ->relationship(
                                name: 'warehouseAddress',
                                titleAttribute: 'address',
                                modifyQueryUsing: fn($query, $get) => $query->where('warehouse_id', $get('warehouse_id'))
                            )
                            ->searchable(['address', 'city'])
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->address} - {$record->city}")
                            ->default(null)
                            ->live()
                            ->columnSpanFull()
                        ,
                        DatePicker::make('delivery_date')
                            ->label(__('purchase-order.delivery_date.label'))
                        ,
                        TextInput::make('shipping_method')
                            ->label(__('purchase-order.shipping_method.label'))
                            ->placeholder(__('purchase-order.shipping_method.placeholder'))
                            ->helperText(__('purchase-order.shipping_method.helper'))
                        ,
                        TextArea::make('delivery_notes')
                            ->label(__('purchase-order.delivery_notes.label'))
                            ->placeholder(__('purchase-order.delivery_notes.placeholder'))
                            ->helperText(__('purchase-order.delivery_notes.helper'))
                            ->columnSpanFull()
                        ,
                        TextArea::make('terms')
                            ->placeholder(__('purchase-order.terms.placeholder'))
                            ->helperText(__('purchase-order.terms.helper'))
                            ->columnSpanFull()
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
                    ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                        $data['line_key'] = filled($data['line_key'] ?? null)
                            ? (string) $data['line_key']
                            : (string) str()->uuid()
                        ;

                        return $data;
                    })
                    ->mutateRelationshipDataBeforeCreateUsing(fn(array $data): array => Arr::except($data, ['line_key', 'request_qty_snapshot', 'ordered_qty_snapshot']))
                    ->mutateRelationshipDataBeforeSaveUsing(fn(array $data): array => Arr::except($data, ['line_key', 'request_qty_snapshot', 'ordered_qty_snapshot']))
                    ->columnSpanFull()
                    ->columns([
                        'lg' => 12,
                    ])
                    ->schema([
                        Hidden::make('line_key')
                            ->default(fn(): string => (string) str()->uuid())
                            ->afterStateHydrated(function ($state, $set): void {
                                if (blank($state)) {
                                    $set('line_key', (string) str()->uuid());
                                }
                            })
                            ->dehydrated()
                        ,
                        Hidden::make('request_qty_snapshot')
                            ->dehydrated()
                        ,
                        Hidden::make('ordered_qty_snapshot')
                            ->dehydrated()
                        ,
                        Select::make('purchase_request_item_id')
                            ->label(__('purchase-order.purchase_request_item.label'))
                            ->options(function ($get): array {
                                $purchaseRequestIds = PurchaseOrder::normalizePurchaseRequestIds((array) ($get('../../purchaseRequests') ?? []));
                                return static::getPurchaseRequestItemOptions($purchaseRequestIds);
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $record = static::getPurchaseRequestItemRecord((int) $value);

                                if (!$record) {
                                    return null;
                                }

                                return "{$record->item?->code} | {$record->item?->name}";
                            })
                            ->preload()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search, $get): array {
                                $purchaseRequestIds = PurchaseOrder::normalizePurchaseRequestIds((array) ($get('../../purchaseRequests') ?? []));
                                return static::getPurchaseRequestItemOptions($purchaseRequestIds, $search);
                            })
                            ->disabled(fn($get): bool => blank(PurchaseOrder::normalizePurchaseRequestIds((array) ($get('../../purchaseRequests') ?? []))))
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->live()
                            ->hint(function ($get): string {
                                $source = static::getPurchaseRequestItemRecord((int) ($get('purchase_request_item_id') ?? 0));

                                if ($source) {
                                    return __('purchase-order.purchase_order_item.source_item.context_value', [
                                        'request_qty' => number_format((float) $source->qty, 2),
                                        'ordered_qty' => number_format($source->getOrderedQty(), 2),
                                        'remaining_qty' => number_format($source->getRemainingQty(), 2),
                                    ]);
                                }

                                return '';
                            })
                            ->afterStateUpdated(function ($state, $set): void {
                                if (!$state) {
                                    $set('item_id', null);
                                    $set('qty', null);
                                    $set('description', null);
                                    static::fillPurchaseRequestItemSnapshot($set, null);
                                    return;
                                }

                                $source = static::getPurchaseRequestItemRecord((int) $state);

                                if (!$source) {
                                    $set('item_id', null);
                                    $set('qty', null);
                                    $set('description', null);
                                    static::fillPurchaseRequestItemSnapshot($set, null);
                                    return;
                                }

                                $set('item_id', $source->item_id);
                                $set('qty', $source->getRemainingQty());
                                $set('description', $source->description);
                                static::fillPurchaseRequestItemSnapshot($set, $source);
                            })
                            ->afterStateHydrated(function ($state, $set, $get): void {
                                if (filled($get('request_qty_snapshot')) || filled($get('ordered_qty_snapshot'))) {
                                    return;
                                }

                                static::fillPurchaseRequestItemSnapshot($set, static::getPurchaseRequestItemRecord((int) $state));
                            })
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
                                    ->options(fn(): array => static::getManualItemOptions())
                                    ->getOptionLabelUsing(function ($value): ?string {
                                        $item = static::getItemRecord((int) $value);

                                        if (!$item) {
                                            return null;
                                        }

                                        return "{$item->code} | {$item->name}";
                                    })
                                    ->preload()
                                    ->searchable()
                                    ->getSearchResultsUsing(fn(string $search): array => static::getManualItemOptions($search))
                                    ->required()
                                    ->live()
                                    ->disabled(fn($get): bool => filled($get('purchase_request_item_id')))
                                    ->dehydrated()
                                ,

                                Textarea::make('description')
                                    ->label(__('common.description.label'))
                                    ->placeholder(__('purchase-order.purchase_order_item.description.placeholder'))
                                    ->helperText(__('purchase-order.purchase_order_item.description.helper'))
                                    ->autosize()
                                    ->columnSpanFull()
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
                                    ->minValue(function ($record, $operation) {
                                        if ($operation === 'edit' && $record) {
                                            return (float) $record->getReceivedQty();
                                        }

                                        return 0.01;
                                    })
                                    ->placeholder(0.01)
                                    ->required()
                                    // ->hint(function ($get): string {
                                    //     $purchaseOrderItemId = (int) ($get('id') ?? 0);

                                    //     if ($purchaseOrderItemId <= 0) {
                                    //         return number_format(0, 2);
                                    //     }

                                    //     static $cache = [];

                                    //     if (!array_key_exists($purchaseOrderItemId, $cache)) {
                                    //         $cache[$purchaseOrderItemId] = \App\Models\PurchaseOrderItem::query()->find($purchaseOrderItemId);
                                    //     }

                                    //     $item = $cache[$purchaseOrderItemId];

                                    //     return number_format($item?->getReceivedQty() ?? 0, 2);
                                    // })
                                    ->hint(fn($get) => '(' . static::getItemUnit((int) ($get('item_id') ?? 0)) . ')')
                                    // ->suffix(fn($get) => static::getItemUnit((int) ($get('item_id') ?? 0)))
                                    ->live(debounce: 500)
                                    ->rule(function ($get, $record) {
                                        return function (string $attribute, $value, $fail) use ($get, $record): void {
                                            $sourceId = (int) $get('purchase_request_item_id');

                                            if ($sourceId <= 0) {
                                                return;
                                            }

                                            $source = static::getPurchaseRequestItemRecord($sourceId);

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
                                // ->columnSpan([
                                //     'default' => 1,
                                //     'md' => 2,
                                //     'xl' => 2,
                                // ])
                                ,
                                TextEntry::make('received_qty')
                                    ->label(__('purchase-order.purchase_order_item.received_qty.label'))
                                    ->numeric()
                                    ->state(function ($get): string {
                                        $purchaseOrderItemId = (int) ($get('id') ?? 0);

                                        if ($purchaseOrderItemId <= 0) {
                                            return number_format(0, 2);
                                        }

                                        static $cache = [];

                                        if (!array_key_exists($purchaseOrderItemId, $cache)) {
                                            $cache[$purchaseOrderItemId] = PurchaseOrderItem::query()->find($purchaseOrderItemId);
                                        }

                                        $item = $cache[$purchaseOrderItemId];

                                        return number_format($item?->getReceivedQty() ?? 0, 2);
                                    })
                                    ->color(fn(?PurchaseOrderItem $record): string => self::getReceivedQtyColumnColor($record))
                                // ->columnSpan([
                                //     'default' => 1,
                                //     'md' => 2,
                                //     'xl' => 2,
                                // ])
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
                                // ->columnSpan([
                                //     'default' => 1,
                                //     'md' => 2,
                                //     'xl' => 2,
                                // ])
                                ,
                                TextEntry::make('subtotal')
                                    ->label(__('purchase-order.subtotal.label'))
                                    ->state(function ($get): string {
                                        return static::formatMoney(static::getCurrentLineBreakdown($get)['subtotal'] ?? 0.0);
                                    })
                                // ->columnSpan([
                                //     'default' => 1,
                                //     'md' => 2,
                                //     'xl' => 2,
                                // ])
                                ,
                            ])
                        ,
                    ])
                    ->collapsible()
                    ->reorderable()
                    ->orderColumn('sort')
                    ->itemLabel('#')
                    ->itemNumbers()
                    ->deleteAction(fn(Action $action) => $action->requiresConfirmation())
                    ->deleteAction(
                        fn(Action $action) => $action
                            ->requiresConfirmation()
                            ->visible(function (array $arguments, Repeater $component): bool {
                                $itemState = $component->getRawState()[$arguments['item']] ?? [];

                                return static::isPurchaseOrderItemDeletable(
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
                            ->afterStateHydrated(fn($component, $state) => $component->state(filled($state) ? (string) ($state + 0) : null))
                            ->dehydrateStateUsing(fn($state) => filled($state) ? $state : null)
                        ,
                        TextInput::make('tax_description')
                            ->label(__('purchase-order.tax_description.label'))
                            ->placeholder(__('purchase-order.tax_description.placeholder'))
                            ->helperText(__('purchase-order.tax_description.helper'))
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
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['subtotal'] ?? 0.0))
                            ->alignEnd()
                        ,
                        TextEntry::make('total_discount')
                            ->label(__('purchase-order.discount.label'))
                            ->state(fn($get): string => '-' . static::formatMoney(static::getSummaryBreakdown($get)['discount'] ?? 0.0))
                            ->color('danger')
                            ->alignEnd()
                        ,
                        TextEntry::make('total_after_discount')
                            ->label(__('purchase-order.after_discount.label'))
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['subtotal_after_discount'] ?? 0.0))
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->alignEnd()
                        ,

                        View::make('components.divider'),

                        TextEntry::make('total_dpp')
                            ->label(__('purchase-order.tax_base.label'))
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['dpp'] ?? 0.0))
                            ->alignEnd()
                        ,
                        TextEntry::make('total_ppn')
                            ->label(fn($get) => __('purchase-order.tax.label', ['percentage' => filled($get('tax_percentage')) ? "({$get('tax_percentage')}%)" : '']))
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['tax_amount'] ?? 0.0))
                            ->color('warning')
                            ->alignEnd()
                        ,
                        TextEntry::make('total')
                            ->label(__('purchase-order.total.label'))
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['total_before_rounding'] ?? 0.0))
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->alignEnd()
                        ,
                        TextEntry::make('total_rounding')
                            ->label(__('purchase-order.rounding.label'))
                            ->state(fn($get): string => static::formatMoney($get('rounding') ?? 0))
                            ->alignEnd()
                        ,
                        TextEntry::make('total_grand_total')
                            ->label(__('purchase-order.grand_total.label'))
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['grand_total'] ?? 0.0))
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->color('primary')
                            ->alignEnd()
                        ,
                    ])
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
                    ->required(fn($get, $record) => $record?->hasWatchedFieldChanges($get()) === true)
                    ->disabled(fn($get, $record) => $record?->hasWatchedFieldChanges($get()) === false)
                    ->afterStateHydrated(fn($component) => $component->state(null))
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
                $vendor = \App\Models\Vendor::find($vendorId);

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
                $purchaseRequestIds = PurchaseOrder::normalizePurchaseRequestIds((array) ($get('purchaseRequests') ?? []));

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

    protected static function getPurchaseRequestItemRecord(?int $sourceId): ?PurchaseRequestItem
    {
        if (!$sourceId) {
            return null;
        }

        static $cache = [];

        if (!array_key_exists($sourceId, $cache)) {
            $cache[$sourceId] = PurchaseRequestItem::query()
                ->with(['item', 'purchaseRequest'])
                ->find($sourceId);
        }

        return $cache[$sourceId];
    }

    protected static function getPurchaseRequestItemOptions(array $purchaseRequestIds, ?string $search = null): array
    {
        $purchaseRequestIds = PurchaseOrder::normalizePurchaseRequestIds($purchaseRequestIds);

        if (blank($purchaseRequestIds)) {
            return [];
        }

        static $cache = [];

        $cacheKey = md5(json_encode([
            'purchase_request_ids' => $purchaseRequestIds,
            'search' => filled($search) ? trim($search) : null,
        ], JSON_THROW_ON_ERROR));

        if (!array_key_exists($cacheKey, $cache)) {
            $query = PurchaseOrder::getCompatiblePurchaseRequestItemsQuery($purchaseRequestIds);

            if (filled($search)) {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder->whereHas('item', function (Builder $itemQuery) use ($search): void {
                        $itemQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })->orWhereHas('purchaseRequest', function (Builder $purchaseRequestQuery) use ($search): void {
                        $purchaseRequestQuery->where('number', 'like', "%{$search}%");
                    });
                });
            }

            $cache[$cacheKey] = $query
                ->limit(50)
                ->get()
                ->mapWithKeys(fn(PurchaseRequestItem $record) => [
                    $record->id => "{$record->item?->code} | {$record->item?->name} | {$record->purchaseRequest?->number}",
                ])
                ->toArray();
        }

        return $cache[$cacheKey];
    }

    protected static function getItemRecord(?int $itemId): ?Item
    {
        if (!$itemId) {
            return null;
        }

        static $cache = [];

        if (!array_key_exists($itemId, $cache)) {
            $cache[$itemId] = Item::query()
                ->with('category')
                ->find($itemId);
        }

        return $cache[$itemId];
    }

    protected static function getManualItemOptions(?string $search = null): array
    {
        static $cache = [];

        $cacheKey = md5(json_encode([
            'search' => filled($search) ? trim($search) : null,
        ], JSON_THROW_ON_ERROR));

        if (!array_key_exists($cacheKey, $cache)) {
            $query = Item::query()
                ->with('category:id,name,allow_po')
                ->whereHas('category', fn(Builder $query): Builder => $query->where('allow_po', true));

            if (filled($search)) {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }

            $cache[$cacheKey] = $query
                ->orderBy('code')
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->mapWithKeys(fn(Item $item) => [
                    $item->id => collect([
                        $item->code,
                        $item->name,
                        $item->category?->name,
                    ])->filter()->implode(' | '),
                ])
                ->toArray();
        }

        return $cache[$cacheKey];
    }

    protected static function getItemUnit(?int $itemId): ?string
    {
        if (!$itemId) {
            return null;
        }

        static $cache = [];

        if (!array_key_exists($itemId, $cache)) {
            $cache[$itemId] = Item::query()
                ->whereKey($itemId)
                ->value('unit');
        }

        return $cache[$itemId];
    }

    protected static function normalizeBreakdownItems(array $items): array
    {
        return collect($items)
            ->filter(fn(mixed $item): bool => is_array($item))
            ->map(function (array $item, int|string $index): array {
                if (blank($item['line_key'] ?? null)) {
                    $item['line_key'] = (string) ($item['id'] ?? "line-{$index}");
                }

                return $item;
            })
            ->values()
            ->all();
    }

    protected static function fillPurchaseRequestItemSnapshot(callable $set, ?PurchaseRequestItem $source): void
    {
        $snapshot = PurchaseOrder::buildPurchaseRequestItemSnapshot($source?->id);

        $set('request_qty_snapshot', $snapshot['request_qty'] ?? null);
        $set('ordered_qty_snapshot', $snapshot['ordered_qty'] ?? null);
    }

    protected static function syncPurchaseRequestStatusSnapshot(callable $set, array $purchaseRequestIds): void
    {
        $set('purchaseRequestStatusSnapshot', PurchaseOrder::buildPurchaseRequestStatusSnapshot($purchaseRequestIds));
    }

    protected static function getOrderBreakdown(
        callable $get,
        string $itemsPath = 'purchaseOrderItems',
        string $basePath = '',
    ): array {
        $resolvePath = static fn(string $field) => $basePath !== ''
            ? "{$basePath}{$field}"
            : $field;
        $items = static::normalizeBreakdownItems((array) ($get($itemsPath) ?? []));
        $discount = (float) ($get($resolvePath('discount')) ?? 0);
        $taxType = $get($resolvePath('tax_type'));
        $taxPercentage = ($get($resolvePath('tax_percentage')) ?? 0);
        $rounding = (float) ($get($resolvePath('rounding')) ?? 0);

        return PurchaseOrder::calculateOrderBreakdown(
            $items,
            $discount,
            $taxType,
            $taxPercentage,
            $rounding,
        );
    }

    protected static function getSummaryBreakdown(callable $get): array
    {
        return static::getOrderBreakdown($get)['summary'] ?? [];
    }

    protected static function getCurrentLineBreakdown(callable $get): array
    {
        $lineKey = $get('line_key');
        $lineId = $get('id');
        $purchaseRequestItemId = $get('purchase_request_item_id');
        $breakdown = static::getOrderBreakdown($get, '../../purchaseOrderItems', '../../');
        $lines = $breakdown['lines'] ?? [];

        $keyCandidates = array_filter([
            filled($lineKey) ? (string) $lineKey : null,
            filled($lineId) ? $lineId : null,
            filled($purchaseRequestItemId) ? $purchaseRequestItemId : null,
        ], fn(mixed $key): bool => $key !== null && $key !== '');

        foreach ($keyCandidates as $keyCandidate) {
            if (array_key_exists($keyCandidate, $lines)) {
                return $lines[$keyCandidate];
            }
        }

        return [
            'subtotal' => 0.0,
            'discount' => 0.0,
            'subtotal_after_discount' => 0.0,
            'dpp' => 0.0,
            'tax_amount' => 0.0,
            'total_before_rounding' => 0.0,
            'grand_total' => 0.0,
        ];
    }

    protected static function resolvePurchaseRequestSelection(array $selectedIds): array
    {
        $selectedIds = PurchaseOrder::normalizePurchaseRequestIds($selectedIds);

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
            ->keyBy('id');

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
            ->all();

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

    protected static function fillHeaderFields(callable $set, ?array $header): void
    {
        $set('warehouse_id', $header['warehouse_id'] ?? null);
        $set('company_id', $header['company_id'] ?? null);
        $set('division_id', $header['division_id'] ?? null);
        $set('project_id', $header['project_id'] ?? null);
    }

    protected static function prunePurchaseOrderItems(callable $set, callable $get, array $selectedPurchaseRequestIds): void
    {
        $selectedPurchaseRequestIds = PurchaseOrder::normalizePurchaseRequestIds($selectedPurchaseRequestIds);
        $items = collect($get('purchaseOrderItems') ?? []);

        if (blank($selectedPurchaseRequestIds)) {
            $filteredItems = $items
                ->filter(fn(array $item): bool => blank($item['purchase_request_item_id'] ?? null))
                ->values()
                ->all();

            if ($filteredItems !== $items->values()->all()) {
                $set('purchaseOrderItems', $filteredItems);
            }

            return;
        }

        $allowedSourceIds = PurchaseRequestItem::query()
            ->whereIn('purchase_request_id', $selectedPurchaseRequestIds)
            ->pluck('id')
            ->map(fn($id): int => (int) $id)
            ->all();

        $filteredItems = $items
            ->filter(function (array $item) use ($allowedSourceIds): bool {
                $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);

                if ($purchaseRequestItemId <= 0) {
                    return true;
                }

                return in_array($purchaseRequestItemId, $allowedSourceIds, true);
            })
            ->values()
            ->all();

        if ($filteredItems !== $items->values()->all()) {
            $set('purchaseOrderItems', $filteredItems);
        }
    }

    protected static function formatMoney(float $amount): string
    {
        return
            // 'Rp ' . 
            number_format($amount, 2, '.', ',');
    }

    protected static function getStatusOptions(?PurchaseOrder $record): array
    {
        $options = $record?->getAvailableStatusOptions() ?? PurchaseOrderStatus::options();

        return collect($options)
            ->mapWithKeys(fn($label, $value): array => [
                (string) $value => $label,
            ])
            ->all();
    }

    protected static function normalizeStatusState(mixed $state): ?string
    {
        if ($state instanceof PurchaseOrderStatus) {
            return (string) $state->value;
        }

        if (blank($state)) {
            return null;
        }

        return (string) $state;
    }

    protected static function shouldDisableStatusOption(string $value, ?PurchaseOrder $record): bool
    {
        if (!$record) {
            return false;
        }

        return !$record->canChangeStatusTo((int) $value);
    }

    public static function isPurchaseOrderItemDeletable(array $itemState = []): bool
    {
        $itemId = $itemState['id'] ?? null;

        if (blank($itemId)) {
            return true;
        }

        $purchaseOrderItem = PurchaseOrderItem::query()->find($itemId);

        if (!$purchaseOrderItem) {
            return true;
        }

        return $purchaseOrderItem->getReceivedQty() <= 0;
    }
}
