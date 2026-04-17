<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderTaxType;
use App\Enums\PurchaseOrderType;
use App\Models\Item;
use App\Models\PurchaseOrder;
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

                            static::totalSection(), // 1.3
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
                            static::otherInfoSection(), // 2.1

                            static::purchaseRequestDetailSection(), // 2.2

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
                            // ->default(PurchaseOrderTaxType::EXCLUDE->value)
                            ->live()
                            // ->afterStateHydrated(fn($component, $state) => $component->state($state ?? PurchaseOrderTaxType::EXCLUDE->value))
                            ->required()
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
                            // ->inlineLabel()
                            ->relationship('vendor', 'name', fn($query) => $query->orderBy('name')->orderBy('code'))
                            ->searchable(['name', 'code'])
                            ->preload()
                            ->required()
                            ->columnSpanFull()
                        ,

                        Section::make('Gudang Proyek')
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
                                    // ->inlineLabel()
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
                                    // ->inlineLabel()
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
                                    // ->inlineLabel()
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
                                    // ->inlineLabel()
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

                Section::make('Informasi Utama')
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
                            // ->inlineLabel()
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
                            ->label('Tanggal Pengiriman')
                        ,

                        TextInput::make('shipping_method')
                            ->label('Metode Pengiriman')
                            ->placeholder('Tuliskan metode pengiriman yang digunakan')
                            ->helperText('Contoh: Pickup')
                        ,

                        TextArea::make('delivery_notes')
                            ->label(__('purchase-order.delivery_notes.label'))
                            // ->inlineLabel()
                            ->placeholder(__('purchase-order.delivery_notes.placeholder'))
                            ->helperText(__('purchase-order.delivery_notes.helper'))
                            ->columnSpanFull()
                        ,
                        TextArea::make('terms')
                            // ->inlineLabel()
                            ->placeholder(__('purchase-order.termin.placeholder'))
                            ->helperText(__('purchase-order.termin.helper'))
                            ->columnSpanFull()
                        ,

                    ])
                ,
            ])
        ;
    }

    protected static function itemSection(): Section
    {
        return Section::make('Form ' . __('purchase-order.section.purchase_order_items.label'))
            ->icon(Heroicon::Cube)
            ->iconColor('primary')
            // ->description(__('purchase-order.section.purchase_order_items.description'))
            ->collapsible()
            ->compact()
            ->columnSpanFull()
            ->schema([
                Repeater::make('purchaseOrderItems')
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
                    ->columns([
                        'default' => 1,
                        'xl' => 12,
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
                            ->label('Sumber Item Pengajuan')
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
                                    static::fillPurchaseRequestItemSnapshot($set, null);
                                    return;
                                }

                                $source = static::getPurchaseRequestItemRecord((int) $state);

                                if (!$source) {
                                    static::fillPurchaseRequestItemSnapshot($set, null);
                                    return;
                                }

                                $set('item_id', $source->item_id);
                                $set('qty', $source->getRemainingQty());
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

                        Select::make('item_id')
                            ->label(
                                __('item.related.code.label') .
                                ' | ' .
                                __('item.related.name.label')
                            )
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
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 6,
                            ])
                        ,

                        TextInput::make('qty')
                            ->numeric()
                            ->minValue(0.01)
                            ->placeholder(0.01)
                            ->required()
                            ->live(debounce: 500)
                            ->suffix(
                                fn($get) => static::getItemUnit((int) ($get('item_id') ?? 0))
                            )
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
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 2,
                            ])
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
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 2,
                            ])
                        ,

                        TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->state(function ($get): string {
                                return static::formatMoney(static::getCurrentLineBreakdown($get)['subtotal'] ?? 0.0);
                            })
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 2,
                            ])
                        ,

                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('purchase-order.purchase_order_item.description.placeholder'))
                            ->helperText(__('purchase-order.purchase_order_item.description.helper'))
                            ->autosize()
                            ->columnSpanFull()
                        ,
                    ])
                    ->addActionLabel('Tambah Item Purchase Order')
                    ->reorderable()
                    ->orderColumn('sort')
                    ->itemLabel('#')
                    ->itemNumbers()
                    ->deleteAction(fn(Action $action) => $action->requiresConfirmation())
                    ->defaultItems(0)
                    ->minItems(1)
                    ->partiallyRenderAfterActionsCalled(false)
                ,
            ])
        ;
    }

    protected static function totalSection(): Section
    {
        return Section::make('Ringkasan Total')
            ->icon(Heroicon::Calculator)
            ->iconColor('primary')
            ->collapsible()
            ->columnSpanFull()
            ->columns(3)
            ->compact()
            ->schema([
                Grid::make()
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
                            ->placeholder('-')
                            ->afterStateHydrated(fn($component, $state) => $component->state(filled($state) ? (string) ($state + 0) : null))
                            ->dehydrateStateUsing(fn($state) => filled($state) ? $state : null)
                        ,

                        TextInput::make('tax_description')
                            ->label(__('purchase-order.tax_description.label'))
                            ->placeholder(__('purchase-order.tax_description.placeholder'))
                            ->helperText('Contoh: PPN 11%')
                            ->columnSpanFull()
                        ,

                        TextInput::make('discount')
                            ->label('Diskon')
                            ->numeric()
                            ->placeholder(0)
                            ->live(debounce: 500)
                            ->dehydrateStateUsing(fn($state) => $state ?? 0)
                            ->columnSpanFull()
                        ,

                        TextInput::make('rounding')
                            ->label('Pembulatan')
                            ->numeric()
                            ->placeholder(0)
                            ->live(debounce: 500)
                            ->dehydrateStateUsing(fn($state) => $state ?? 0)
                            ->columnSpanFull()
                        ,
                    ])
                ,

                Fieldset::make('Rincian Total')
                    ->columns(1)
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('total_subtotal')
                            ->label('Subtotal')
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['subtotal'] ?? 0.0))
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,

                        TextEntry::make('total_discount')
                            ->label('Diskon')
                            ->state(fn($get): string => '-' . static::formatMoney(static::getSummaryBreakdown($get)['discount'] ?? 0.0))
                            ->color('danger')
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,

                        TextEntry::make('total_after_discount')
                            ->label('Subtotal Setelah Diskon')
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['subtotal_after_discount'] ?? 0.0))
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,

                        View::make('components.divider'),

                        TextEntry::make('total_dpp')
                            ->label('DPP')
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['dpp'] ?? 0.0))
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,

                        TextEntry::make('total_ppn')
                            ->label(fn($get) => filled($get('tax_percentage')) ? "PPN ({$get('tax_percentage')}%)" : 'PPN')
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['tax_amount'] ?? 0.0))
                            ->color('warning')
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,

                        TextEntry::make('total')
                            ->label('Total')
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['total_before_rounding'] ?? 0.0))
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,

                        TextEntry::make('total_rounding')
                            ->label('Pembulatan')
                            ->state(fn($get): string => static::formatMoney($get('rounding') ?? 0))
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,

                        TextEntry::make('total_grand_total')
                            ->label('Total Pembayaran')
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['grand_total'] ?? 0.0))
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->color('primary')
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,
                    ])
                ,
            ])
        ;
    }

    protected static function otherInfoSection(): Section
    {
        return Section::make(__('purchase-order.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            // ->description(__('purchase-order.section.other_info.description'))
            ->collapsible()
            ->columnSpanFull()
            ->columns(1)
            ->compact()
            ->schema([
                // Select::make('status')
                //     ->options(fn($record) => $record->getAvailableStatusOptions())
                //     ->native(false)
                //     ->required()
                //     ->disableOptionWhen(function ($value, $record) {
                //         if (!$record) {
                //             return false;
                //         }

                //         return $record && !$record->canChangeStatusTo($value);
                //     })
                //     ->columnSpan(1)
                //     ->visibleOn('edit')
                // ,
                // TextInput::make('memo')
                //     ->placeholder(__('purchase-order.memo.placeholder'))
                //     ->helperText(__('purchase-order.memo.helper')),
                Textarea::make('notes')
                    ->label(__('purchase-order.notes.label'))
                    ->placeholder(__('purchase-order.notes.placeholder'))
                    ->helperText(__('purchase-order.notes.helper'))
                    ->autosize()
                    ->columnSpanFull(),

                UserEntry::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->visibleOn('edit')
                    ->columnSpanFull()
                ,

                TextEntry::make('updated_at')->date()
                    ->label(__('common.updated_at.label'))
                    ->color('gray')
                    ->size(TextSize::Small)
                    ->visibleOn('edit')
                ,
                TextEntry::make('deleted_at')->date()
                    ->label(__('common.deleted_at.label'))
                    ->color('gray')
                    ->size(TextSize::Small)
                    ->visible(fn($state) => $state != null)
                ,

                Textarea::make('info')
                    ->label(__('purchase-order.info.label'))
                    ->placeholder(__('purchase-order.info.placeholder'))
                    ->helperText(__('purchase-order.info.helper'))
                    ->autosize()
                    ->visible(fn($record, $operation) => $operation === 'edit' && !$record?->hasStatus(PurchaseOrderStatus::DRAFT))
                    ->required(fn($get, $record) => $record?->hasWatchedFieldChanges($get()) === true)
                    ->disabled(fn($get, $record) => $record?->hasWatchedFieldChanges($get()) === false)
                    ->afterStateHydrated(fn($component) => $component->state(null))
                    ->columnSpanFull()
                ,

                TextEntry::make('info')
                    ->label(__('purchase-order.revision_history.label'))
                    ->formatStateUsing(
                        fn($state) => collect(explode("\n", $state))
                            ->map(fn($line) => "• " . e($line))
                            ->implode('<br>')
                    )
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->visible(fn($record) => !$record?->hasStatus(PurchaseOrderStatus::DRAFT))
                    ->columnSpanFull()
                    ->visibleOn('edit')
                ,
            ]);
    }

    protected static function purchaseRequestDetailSection(): Section
    {
        return Section::make(__('purchase-request.model.plural_label'))
            ->icon(Heroicon::DocumentText)
            ->iconColor('primary')
            ->collapsible()
            ->columnSpanFull()
            ->compact()
            ->schema(
                function ($get) {
                    $ids = PurchaseOrder::normalizePurchaseRequestIds((array) ($get('purchaseRequests') ?? []));

                    if (blank($ids)) {
                        return [
                            TextEntry::make('purchase_request_detail_empty')
                                ->hiddenLabel()
                                ->state(__('Pilih pengajuan untuk melihat detail'))
                            ,
                        ];
                    }

                    return PurchaseRequest::query()
                        ->with(['user', 'warehouseAddress'])
                        ->whereIn('id', $ids)
                        ->get()
                        ->map(function (PurchaseRequest $purchaseRequest) {
                            return Section::make()
                                ->hiddenLabel()
                                ->compact()
                                ->columns([
                                    'default' => 2,
                                ])
                                ->schema([
                                    TextEntry::make("purchase_request_{$purchaseRequest->id}_number")
                                        ->hiddenLabel()
                                        ->state($purchaseRequest->number)
                                        ->icon(Heroicon::Hashtag)
                                        ->iconColor('primary')
                                        ->fontFamily(FontFamily::Mono)
                                        ->weight(FontWeight::Bold)
                                        ->columnSpanFull()
                                    ,
                                    TextEntry::make("purchase_request_{$purchaseRequest->id}_status")
                                        ->hiddenLabel()
                                        ->icon($purchaseRequest->status->icon())
                                        ->state($purchaseRequest->status->label())
                                        ->badge()
                                        ->color($purchaseRequest->status->color())
                                    ,
                                    TextEntry::make("purchase_request_{$purchaseRequest->id}_created_at")
                                        ->hiddenLabel()
                                        ->state($purchaseRequest->created_at)
                                        ->icon(Heroicon::CalendarDays)
                                        ->iconColor('primary')
                                        ->date()
                                    ,
                                    TextEntry::make("purchase_request_{$purchaseRequest->id}_warehouse_address")
                                        ->hiddenLabel()
                                        ->icon(Heroicon::MapPin)
                                        ->iconColor('primary')
                                        ->state($purchaseRequest->warehouseAddress
                                            ? collect([$purchaseRequest->warehouseAddress->address, $purchaseRequest->warehouseAddress->city])->filter()->join(' - ')
                                            : '')
                                        ->html()
                                        ->placeholder('-')
                                        ->color('gray')
                                        ->columnSpanFull()
                                    ,
                                    TextEntry::make("purchase_request_{$purchaseRequest->id}_description")
                                        ->hiddenLabel()
                                        ->columnSpanFull()
                                        ->color('gray')
                                        ->placeholder('-')
                                        ->state($purchaseRequest->description)
                                        ->html()
                                    ,

                                    UserEntry::make("purchase_request_{$purchaseRequest->id}_user")
                                        ->hiddenLabel()
                                        ->state($purchaseRequest->user)
                                        ->columnSpanFull()
                                    ,
                                ])
                            ;
                        })
                        ->toArray()
                    ;
                },
            )
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
}
