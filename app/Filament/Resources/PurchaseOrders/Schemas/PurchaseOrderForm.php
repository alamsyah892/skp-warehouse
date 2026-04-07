<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderTaxType;
use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Filament\Actions\Action;
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
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
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
                    // 'lg' => 1,
                    'xl' => 4,
                    '2xl' => 4,
                ])
                ->schema([
                    Grid::make() // left / 1
                        ->columnSpan([
                            'xl' => 3,
                            '2xl' => 3,
                        ])
                        ->schema([
                            static::dataSection(), // 1.1

                            static::itemSection(), // 1.2

                            static::totalSection(), // 1.3
                        ])
                    ,

                    Grid::make() // right / 2
                        ->columnSpan([
                            'xl' => 1,
                            '2xl' => 1,
                        ])
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

    protected static function dataSection(): Section
    {
        return Section::make('Form ' . __('purchase-order.section.main_info.label'))
            ->icon(Heroicon::ShoppingCart)
            ->iconColor('primary')
            ->description(__('purchase-order.section.main_info.description'))
            ->collapsible()
            ->columnSpanFull()
            ->columns([
                'default' => 1,
                'xl' => 12,
            ])
            ->compact()
            ->schema([
                Grid::make([
                    'default' => 1,
                    'xl' => 1,
                ])
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 7,
                    ])
                    ->schema([
                        Fieldset::make(__('purchase-order.fieldset.warehouse_project.label'))
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->schema([
                                Select::make('warehouse_id')
                                    ->label(__('warehouse.model.label'))
                                    ->relationship(
                                        'warehouse',
                                        'name',
                                        fn($query) => $query
                                            ->when(
                                                auth()->user()->warehouses()->exists(),
                                                fn($q) => $q->whereIn(
                                                    'warehouses.id',
                                                    auth()->user()->warehouses->pluck('id')
                                                )
                                            )->orderBy('name')->orderBy('code'),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($set) {
                                        $set('company_id', null);
                                        $set('division_id', null);
                                        $set('project_id', null);
                                        $set('warehouse_address_id', null);
                                    })
                                    ->required()
                                    ->disabled(
                                        fn($get, string $operation) =>
                                        $operation === 'edit' || filled($get('purchaseRequests'))
                                    )
                                    ->columnSpan(1)
                                    ->dehydrated()
                                ,
                                Select::make('company_id')
                                    ->label(__('purchase-request.company.label'))
                                    ->relationship(
                                        'company',
                                        'alias',
                                        fn($query) => $query->orderBy('alias')->orderBy('code'),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn($set) => $set('project_id', null))
                                    ->required()
                                    ->disabled(
                                        fn($get, string $operation) =>
                                        $operation === 'edit' || blank($get('warehouse_id')) || filled($get('purchaseRequests'))
                                    )
                                    ->columnSpan(1)
                                    ->dehydrated()
                                ,
                                Select::make('division_id')
                                    ->label(__('division.model.label'))
                                    ->relationship(
                                        'division',
                                        'name',
                                        function ($query, $get) {
                                            $companyId = $get('company_id');

                                            $query->when($companyId, function ($q) use ($companyId) {
                                                $q->whereHas(
                                                    'companies',
                                                    fn($qq) => $qq->where('companies.id', $companyId)
                                                )->orWhereDoesntHave('companies');
                                            })->orderBy('name')->orderBy('code');
                                        }
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->required()
                                    ->disabled(
                                        fn($get, string $operation) =>
                                        $operation === 'edit' || blank($get('company_id')) || filled($get('purchaseRequests'))
                                    )
                                    ->columnSpan(1)
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
                                                ->when($companyId, function ($q) use ($companyId) {
                                                    $q->where(function ($qq) use ($companyId) {
                                                        $qq->whereHas(
                                                            'companies',
                                                            fn($q) => $q->where('companies.id', $companyId)
                                                        )->orWhereDoesntHave('companies');
                                                    });
                                                })
                                                ->when($warehouseId, function ($q) use ($warehouseId) {
                                                    $q->where(function ($qq) use ($warehouseId) {
                                                        $qq->whereHas(
                                                            'warehouses',
                                                            fn($q) => $q->where('warehouses.id', $warehouseId)
                                                        )->orWhereDoesntHave('warehouses');
                                                    });
                                                })
                                                ->where('allow_po', true)
                                                ->orderBy('name')->orderBy('code')
                                            ;
                                        }
                                    )
                                    ->searchable(['name', 'code', 'po_code'])
                                    ->getOptionLabelFromRecordUsing(
                                        fn($record) =>
                                        "{$record->code} / {$record->po_code} | {$record->name}"
                                    )
                                    ->preload()
                                    ->live()
                                    ->required()
                                    ->disabled(
                                        fn($get, string $operation) =>
                                        $operation === 'edit' ||
                                        blank($get('warehouse_id')) ||
                                        blank($get('company_id')) ||
                                        filled($get('purchaseRequests'))
                                    )
                                    ->columnSpan(1)
                                    ->dehydrated()
                                ,

                                Select::make('purchaseRequests')
                                    ->label(__('purchase-request.model.plural_label'))
                                    ->relationship(
                                        name: 'purchaseRequests',
                                        titleAttribute: 'number',
                                        modifyQueryUsing: function (Builder $query, Select $component, $get): Builder {
                                            $selectedIds = PurchaseOrder::normalizePurchaseRequestIds((array) $component->getState());
                                            $selection = static::resolvePurchaseRequestSelection($selectedIds);
                                            $header = $selection['header'];

                                            // Jika belum ada PR yang dipilih, gunakan filter dari input warehouse/company/division/project (Opsi 2)
                                            if (!$header) {
                                                $header = array_filter([
                                                    'warehouse_id' => $get('warehouse_id'),
                                                    'company_id' => $get('company_id'),
                                                    'division_id' => $get('division_id'),
                                                    'project_id' => $get('project_id'),
                                                ]);
                                            }

                                            $query->where(fn($q) => $q->where('status', PurchaseRequestStatus::APPROVED)->orWhere('status', PurchaseRequestStatus::ORDERED));

                                            if ($header) {
                                                $query->where(function (Builder $scopedQuery) use ($header, $selectedIds): void {
                                                    $scopedQuery->where(function (Builder $compatibleQuery) use ($header): void {
                                                        foreach ($header as $field => $value) {
                                                            $compatibleQuery->where("purchase_requests.{$field}", $value);
                                                        }
                                                    });

                                                    if (!empty($selectedIds)) {
                                                        $scopedQuery->orWhereIn('purchase_requests.id', $selectedIds);
                                                    }
                                                });
                                            }

                                            return $query->orderByDesc('purchase_requests.id');
                                        },
                                    )
                                    ->multiple()
                                    ->searchable(['number', 'description'])
                                    ->getOptionLabelFromRecordUsing(fn(PurchaseRequest $record): string => "{$record->number}")
                                    ->preload()
                                    ->live()
                                    ->required()
                                    ->columnSpanFull()
                                    ->helperText(__('purchase-order.purchase_requests.helper'))
                                    ->afterStateUpdated(function ($state, $set, $get): void {
                                        $selection = static::resolvePurchaseRequestSelection((array) $state);

                                        if ($selection['ids'] !== PurchaseOrder::normalizePurchaseRequestIds((array) $state)) {
                                            $set('purchaseRequests', $selection['ids']);
                                        }

                                        // Hanya isi field header jika ada PR yang dipilih
                                        if ($selection['header']) {
                                            static::fillHeaderFields($set, $selection['header']);
                                        }
                                        static::prunePurchaseOrderItems($set, $get, $selection['ids']);
                                        static::touchPaymentSummary($set);
                                    })
                                    ->afterStateHydrated(function ($state, $set, $get): void {
                                        $selection = static::resolvePurchaseRequestSelection((array) $state);

                                        if ($selection['ids'] !== PurchaseOrder::normalizePurchaseRequestIds((array) $state)) {
                                            $set('purchaseRequests', $selection['ids']);
                                        }

                                        // Hanya isi field header jika ada PR yang dipilih
                                        if ($selection['header']) {
                                            static::fillHeaderFields($set, $selection['header']);
                                        }
                                        static::prunePurchaseOrderItems($set, $get, $selection['ids']);
                                        static::touchPaymentSummary($set);
                                    })
                                ,
                            ])
                        ,

                        Fieldset::make('Informasi Pengiriman')
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->schema([
                                Select::make('warehouse_address_id')
                                    ->label(__('purchase-request.warehouse_address.label'))
                                    ->relationship(
                                        'warehouseAddress',
                                        'address',
                                        fn($query, $get) => $query->where('warehouse_id', $get('warehouse_id'))
                                    )
                                    ->getOptionLabelFromRecordUsing(
                                        fn($record) => "{$record->address} - {$record->city}"
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->default(null)
                                    ->disabled(fn($get) => blank($get('warehouse_id')))
                                    ->live()
                                    ->columnSpan(1)
                                ,
                                TextInput::make('delivery_info')
                                    ->label(__('purchase-order.delivery_info.label'))
                                    ->placeholder(__('purchase-order.delivery_info.placeholder'))
                                    ->helperText(__('purchase-order.delivery_info.helper'))
                                    ->columnSpan(1)
                                ,
                            ])
                        ,
                    ])
                ,

                Fieldset::make(__('purchase-order.fieldset.info.label'))
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 5,
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('number')
                            ->label(__('purchase-order.number.label'))
                            ->hint('Auto-generated')
                            ->hintIcon('heroicon-m-information-circle')
                            ->readOnly()
                            ->visibleOn('edit')
                            ->columnSpan(1)
                            ->dehydrated(false),

                        Select::make('vendor_id')
                            ->label(__('vendor.model.label'))
                            ->relationship('vendor', 'name', fn($query) => $query->orderBy('name')->orderBy('code'))
                            ->searchable(['name', 'code'])
                            ->required()
                            ->preload()
                            ->columnSpan(fn(string $operation): int => $operation === 'edit' ? 1 : 2)
                        ,

                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('purchase-order.description.placeholder'))
                            ->helperText(__('purchase-order.description.helper'))
                            ->live()
                            ->autosize()
                            ->columnSpanFull()
                        ,
                        TextInput::make('termin')
                            ->label('Termin Pembayaran')
                            ->placeholder(__('purchase-order.termin.placeholder'))
                            ->helperText(__('purchase-order.termin.helper'))
                            ->columnSpanFull()
                        ,

                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->columnSpanFull()
                            ->schema([
                                Select::make('tax_type')
                                    ->label(__('purchase-order.tax_type.label'))
                                    ->options(PurchaseOrderTaxType::options())
                                    ->native(false)
                                    ->default(PurchaseOrderTaxType::EXCLUDE->value)
                                    ->afterStateHydrated(fn($component, $state) => $component->state($state ?? PurchaseOrderTaxType::EXCLUDE->value))
                                    ->live()
                                    ->required()
                                ,
                                Select::make('tax_percentage')
                                    ->label(__('purchase-order.tax_percentage.label'))
                                    ->options(PurchaseOrder::getTaxPercentageOptions())
                                    ->native(false)
                                    ->default((string) PurchaseOrder::DEFAULT_TAX_PERCENTAGE)
                                    ->afterStateHydrated(fn($component, $state) => $component->state(filled($state) ? (string) ((float) $state + 0) : (string) PurchaseOrder::DEFAULT_TAX_PERCENTAGE))
                                    ->dehydrateStateUsing(fn($state): ?float => filled($state) ? (float) $state : null)
                                    ->live()
                                    ->required()
                                ,
                            ])
                        ,
                        TextInput::make('tax_description')
                            ->label(__('purchase-order.tax_description.label'))
                            ->placeholder(__('purchase-order.tax_description.placeholder'))
                            ->columnSpanFull()
                        ,

                        // Select::make('status')
                        //     ->options(fn(?PurchaseOrder $record): array => static::getStatusOptions($record))
                        //     ->formatStateUsing(fn($state): ?string => static::normalizeStatusState($state))
                        //     ->dehydrateStateUsing(fn($state): ?int => filled($state) ? (int) $state : null)
                        //     ->native(false)
                        //     ->required()
                        //     ->disableOptionWhen(fn(string $value, ?PurchaseOrder $record): bool => static::shouldDisableStatusOption($value, $record))
                        //     ->visibleOn('edit')
                        // ,

                        Select::make('status')
                            ->options(fn($record) => $record->getAvailableStatusOptions())
                            ->native(false)
                            ->required()
                            ->disableOptionWhen(function ($value, $record) {
                                if (!$record) {
                                    return false;
                                }

                                return $record && !$record->canChangeStatusTo($value);
                            })
                            ->columnSpan(1)
                            ->visibleOn('edit')
                        ,
                    ])
                ,
            ]);
    }

    protected static function itemSection(): Section
    {
        return Section::make('Form ' . __('purchase-order.section.purchase_order_items.label'))
            ->icon(Heroicon::OutlinedCube)
            ->iconColor('primary')
            ->description(__('purchase-order.section.purchase_order_items.description'))
            ->collapsible()
            ->compact()
            ->columnSpanFull()
            ->schema([
                Repeater::make('purchaseOrderItems')
                    ->hiddenLabel()
                    ->relationship()
                    ->itemLabel(function (array $state): string {
                        // $source = static::getPurchaseRequestItemRecord((int) ($state['purchase_request_item_id'] ?? 0));
            
                        // if ($source) {
                        //     return collect([
                        //         $source->item?->code,
                        //         $source->item?->name,
                        //         $source->purchaseRequest?->number,
                        //     ])->filter()->implode(' | ');
                        // }
            
                        // $item = static::getItemRecord((int) ($state['item_id'] ?? 0));
            
                        // if ($item) {
                        //     return collect([
                        //         $item->code,
                        //         $item->name,
                        //         'Manual',
                        //     ])->filter()->implode(' | ');
                        // }
            
                        return 'Item';
                    })
                    ->addActionLabel('Tambah Item Purchase Order')
                    ->columns([
                        'default' => 1,
                        'xl' => 12,
                    ])
                    ->schema([
                        Grid::make()
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 5,
                            ])
                            ->schema([
                                Hidden::make('line_key')
                                    ->default(fn(): string => (string) str()->uuid())
                                    ->afterStateHydrated(function ($state, $set): void {
                                        if (blank($state)) {
                                            $set('line_key', (string) str()->uuid());
                                        }
                                    })
                                    ->dehydrated(false)
                                ,
                                Select::make('purchase_request_item_id')
                                    ->label('Sumber Item Pengajuan')
                                    ->options(function ($get): array {
                                        $purchaseRequestIds = PurchaseOrder::normalizePurchaseRequestIds((array) ($get('../../purchaseRequests') ?? []));
                                        return static::getPurchaseRequestItemOptions($purchaseRequestIds);
                                    })
                                    ->getSearchResultsUsing(function (string $search, $get): array {
                                        $purchaseRequestIds = PurchaseOrder::normalizePurchaseRequestIds((array) ($get('../../purchaseRequests') ?? []));
                                        return static::getPurchaseRequestItemOptions($purchaseRequestIds, $search);
                                    })
                                    // ->getOptionLabelUsing(function ($value): ?string {
                                    //     $record = PurchaseRequestItem::with(['item', 'purchaseRequest'])->find($value);

                                    //     if (!$record) {
                                    //         return null;
                                    //     }

                                    //     return "{$record->item?->code} | {$record->item?->name}";
                                    // })
                                    ->preload()
                                    ->searchable()
                                    ->disabled(fn($get): bool => blank(PurchaseOrder::normalizePurchaseRequestIds((array) ($get('../../purchaseRequests') ?? []))))
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->live()
                                    ->helperText(function ($get): string {
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
                                            return;
                                        }

                                        $source = static::getPurchaseRequestItemRecord((int) $state);

                                        if (!$source) {
                                            return;
                                        }

                                        $set('item_id', $source->item_id);
                                        $set('qty', $source->getRemainingQty());
                                    })
                                    ->columnSpanFull()
                                ,
                                Select::make('item_id')
                                    ->label('Item')
                                    ->options(fn(): array => static::getManualItemOptions())
                                    ->getSearchResultsUsing(fn(string $search): array => static::getManualItemOptions($search))
                                    ->getOptionLabelUsing(function ($value): ?string {
                                        $item = static::getItemRecord((int) $value);

                                        if (!$item) {
                                            return null;
                                        }

                                        return "{$item->code} | {$item->name}";
                                    })
                                    ->preload()
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->disabled(fn($get): bool => filled($get('purchase_request_item_id')))
                                    ->dehydrated()
                                    ->columnSpanFull()
                                ,
                            ])
                        ,

                        Grid::make()
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 7,
                            ])
                            ->columns([
                                'default' => 1,
                                'md' => 6,
                                'xl' => 12,
                            ])
                            ->schema([
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

                                            $source = PurchaseRequestItem::query()->find($sourceId);

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
                                        'xl' => 4,
                                    ])
                                ,

                                TextInput::make('price')
                                    ->label(function ($get): string {
                                        return $get('../../tax_type') === PurchaseOrderTaxType::INCLUDE ->value
                                            ? __('purchase-order.purchase_order_item.price.include_label')
                                            : __('purchase-order.purchase_order_item.price.label');
                                    })
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder(0)
                                    ->prefix('Rp')
                                    ->required()
                                    ->live(debounce: 500)
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 4,
                                    ])
                                ,
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->state(function ($get): string {
                                        return static::formatMoney(static::getCurrentLineBreakdown($get)['gross_subtotal'] ?? 0.0);
                                    })
                                    ->color('gray')
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 4,
                                    ])
                                ,

                                TextInput::make('discount')
                                    ->label('Diskon')
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder(0)
                                    ->prefix('Rp')
                                    ->dehydrated()
                                    ->live(debounce: 500)
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 4,
                                    ])
                                ,
                                TextEntry::make('discount_percentage')
                                    ->label('Persentase Diskon')
                                    ->state(function ($get): string {
                                        $lineBreakdown = static::getCurrentLineBreakdown($get);
                                        $grossSubtotal = (float) ($lineBreakdown['gross_subtotal'] ?? 0);
                                        $itemDiscount = (float) ($lineBreakdown['item_discount'] ?? 0);

                                        if ($grossSubtotal <= 0 || $itemDiscount <= 0) {
                                            return '';
                                        }

                                        return number_format(($itemDiscount / $grossSubtotal) * 100, 0) . '%';
                                    })
                                    ->badge()
                                    ->color('danger')
                                    ->size(TextSize::Large)
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 4,
                                    ])
                                ,
                                TextEntry::make('after_discount')
                                    ->label('Subtotal Setelah Diskon')
                                    ->state(function ($get): string {
                                        return static::formatMoney(static::getCurrentLineBreakdown($get)['gross_after_discount'] ?? 0.0);
                                    })
                                    ->color('gray')
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 4,
                                    ])
                                ,


                                TextEntry::make('dpp')
                                    ->label('DPP')
                                    ->state(function ($get): string {
                                        return static::formatMoney(static::getCurrentLineBreakdown($get)['tax_base'] ?? 0.0);
                                    })
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 4,
                                    ])
                                ,
                                TextEntry::make('tax')
                                    ->label(fn($get) => $get('../../tax_percentage') ? 'PPN (' . $get('../../tax_percentage') . '%)' : 'PPN')
                                    ->state(function ($get): string {
                                        return static::formatMoney(static::getCurrentLineBreakdown($get)['tax_amount'] ?? 0.0);
                                    })
                                    ->color('warning')
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 4,
                                    ])
                                ,
                                TextEntry::make('total')
                                    ->label('Total')
                                    ->state(function ($get): string {
                                        return static::formatMoney(static::getCurrentLineBreakdown($get)['total'] ?? 0.0);
                                    })
                                    // ->size(TextSize::Large)
                                    ->color('primary')
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 4,
                                    ])
                                ,
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
                    ->reorderable()
                    ->orderColumn('sort')
                    ->itemNumbers()
                    ->deleteAction(fn(Action $action) => $action
                        ->requiresConfirmation()
                        ->after(function ($set): void {
                            static::touchPaymentSummary($set);
                        }))
                    ->afterDelete(function ($set): void {
                        static::touchPaymentSummary($set);
                    })
                    ->defaultItems(0)
                    ->minItems(1)
                    ->partiallyRenderAfterActionsCalled(false)
                    ->live()
                    ->afterStateUpdated(function ($set): void {
                        static::touchPaymentSummary($set);
                    })
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
            ->columns(2)
            ->compact()
            ->schema([

                Grid::make()
                    ->columns(2)
                    ->schema([
                        // TextInput::make('discount')
                        //     ->label(__('purchase-order.discount.label'))
                        //     ->numeric()
                        //     ->minValue(0)
                        //     ->placeholder(0)
                        //     ->prefix('Rp')
                        //     ->live()
                        //     ->inlineLabel()
                        //     ->columnSpanFull()
                        // ,
                        TextInput::make('rounding')
                            ->label('Pembulatan')
                            ->numeric()
                            ->placeholder(0)
                            ->prefix('Rp')
                            ->live(debounce: 500)
                            ->inlineLabel()
                            ->columnSpanFull()
                        ,
                    ])
                ,

                Fieldset::make('Rincian Total')
                    ->key(function ($get): string {
                        return 'payment-summary-' . md5(json_encode([
                            'items' => $get('purchaseOrderItems') ?? [],
                            'discount' => $get('discount'),
                            'tax_type' => $get('tax_type'),
                            'tax_percentage' => $get('tax_percentage'),
                            'rounding' => $get('rounding'),
                            'refresh_key' => $get('payment_summary_refresh_key'),
                        ], JSON_THROW_ON_ERROR));
                    })
                    ->columns(1)
                    ->inlineLabel()
                    ->schema([
                        Hidden::make('payment_summary_refresh_key')
                            ->default(fn(): string => (string) str()->uuid())
                            ->dehydrated(false)
                        ,
                        TextEntry::make('total_subtotal')
                            ->label('Subtotal')
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['gross_subtotal'] ?? 0.0))
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,
                        TextEntry::make('total_discount')
                            ->label('Diskon')
                            ->state(fn($get): string => '-' . static::formatMoney(static::getSummaryBreakdown($get)['discount_total'] ?? 0.0))
                            ->color('danger')
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,
                        TextEntry::make('total_after_discount')
                            ->label('Subtotal Setelah Diskon')
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['gross_after_discount'] ?? 0.0))
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,
                        View::make('components.divider'),
                        TextEntry::make('total_dpp')
                            ->label('DPP')
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['tax_base'] ?? 0.0))
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,
                        TextEntry::make('total_ppn')
                            ->label(fn($get) => $get('tax_percentage') ? 'PPN (' . $get('tax_percentage') . '%)' : 'PPN')
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['tax_amount'] ?? 0.0))
                            ->color('warning')
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,
                        TextEntry::make('total')
                            ->label('Total')
                            ->state(fn($get): string => static::formatMoney(static::getSummaryBreakdown($get)['before_rounding'] ?? 0.0))
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->inlineLabel()
                            ->alignEnd()
                            ->columnSpanFull()
                        ,
                        TextEntry::make('total_rounding')
                            ->label('Pembulatan')
                            ->state(fn($get): string => static::formatMoney(
                                $get('rounding') ?? 0
                            ))
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
            ]);
    }

    protected static function otherInfoSection(): Section
    {
        return Section::make('Form ' . __('purchase-order.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->description(__('purchase-order.section.other_info.description'))
            ->collapsible()
            ->columnSpanFull()
            ->columns(1)
            ->compact()
            ->schema([
                TextInput::make('memo')
                    ->placeholder(__('purchase-order.memo.placeholder'))
                    ->helperText(__('purchase-order.memo.helper')),
                Textarea::make('notes')
                    ->label(__('purchase-order.notes.label'))
                    ->placeholder(__('purchase-order.notes.placeholder'))
                    ->helperText(__('purchase-order.notes.helper'))
                    ->autosize()
                    ->columnSpanFull(),
                Textarea::make('info')
                    ->label(__('purchase-order.info.label'))
                    ->placeholder(__('purchase-order.info.placeholder'))
                    ->helperText(__('purchase-order.info.helper'))
                    ->autosize()
                    ->visible(fn($record, $operation) => $operation === 'edit' && !$record?->hasStatus(PurchaseOrderStatus::DRAFT))
                    ->required(fn($get, $record) => $record?->hasWatchedFieldChanges($get()) === true)
                    ->disabled(fn($get, $record) => $record?->hasWatchedFieldChanges($get()) === false)
                    ->afterStateHydrated(fn($component) => $component->state(null))
                    ->columnSpanFull(),
                TextEntry::make('info')
                    ->label(__('purchase-order.revision_history.label'))
                    ->placeholder('-')
                    ->visible(fn($record, $operation) => $operation === 'edit' && !$record?->hasStatus(PurchaseOrderStatus::DRAFT))
                    ->columnSpanFull()
                    ->formatStateUsing(
                        fn($state) => collect(explode("\n", $state))
                            ->map(fn($line) => '&bull; ' . e($line))
                            ->implode('<br>')
                    )
                    ->html()
                    ->color('gray'),
                UserEntry::make('user')
                    ->wrapped()
                    ->visibleOn('edit'),
                TextEntry::make('created_at')->date()
                    ->label(__('common.created_at.label'))
                    ->visibleOn('edit')
                    ->color('gray')
                    ->size(TextSize::Small),
                TextEntry::make('updated_at')->date()
                    ->label(__('common.updated_at.label'))
                    ->visibleOn('edit')
                    ->color('gray')
                    ->size(TextSize::Small),
                TextEntry::make('deleted_at')->date()
                    ->label(__('common.deleted_at.label'))
                    ->visibleOn('edit')
                    ->color('gray')
                    ->size(TextSize::Small)
                    ->visible(fn($state) => $state != null),
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
                        ->map(function (PurchaseRequest $pr) {
                            return Fieldset::make($pr->number)
                                ->columns(1)
                                ->schema([
                                    TextEntry::make("purchase_request_{$pr->id}_created_at")
                                        ->hiddenLabel()
                                        ->state($pr->created_at)
                                        ->date()
                                        ->icon(Heroicon::CalendarDays)
                                        ->iconColor('primary')
                                    ,

                                    UserEntry::make("purchase_request_{$pr->id}_user")
                                        ->hiddenLabel()
                                        ->state($pr->user)
                                        ->wrapped()
                                    ,

                                    TextEntry::make("purchase_request_{$pr->id}_status")
                                        ->hiddenLabel()
                                        ->state($pr->status->label())
                                        ->icon($pr->status->icon())
                                        ->badge()
                                        ->color($pr->status->color())
                                    ,

                                    TextEntry::make("purchase_request_{$pr->id}_warehouse_address")
                                        ->label(__('purchase-request.warehouse_address.label'))
                                        ->hiddenLabel()
                                        ->columnSpanFull()
                                        ->color('gray')
                                        ->icon(Heroicon::MapPin)
                                        ->iconColor('primary')
                                        ->placeholder('-')
                                        ->state($pr->warehouseAddress ? $pr->warehouseAddress?->address . ' - ' . $pr->warehouseAddress?->city : '')
                                    ,

                                    TextEntry::make("purchase_request_{$pr->id}_description")
                                        ->label(__('common.description.label'))
                                        ->hiddenLabel()
                                        ->state($pr->description)
                                        ->placeholder('-')
                                        ->color('gray')
                                        ->size(TextSize::Small)
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

    protected static function getOrderBreakdown(
        array $items,
        mixed $discount,
        mixed $taxType,
        mixed $taxPercentage,
        mixed $rounding = 0,
    ): array {
        static $cache = [];

        $key = md5(json_encode([
            'items' => collect($items)
                ->map(fn(array $item): array => [
                    'line_key' => $item['line_key'] ?? null,
                    'purchase_request_item_id' => $item['purchase_request_item_id'] ?? null,
                    'qty' => (float) ($item['qty'] ?? 0),
                    'price' => (float) ($item['price'] ?? 0),
                    'discount' => (float) ($item['discount'] ?? 0),
                ])
                ->values()
                ->all(),
            'discount' => (float) ($discount ?? 0),
            'tax_type' => PurchaseOrder::normalizeTaxType($taxType)?->value,
            'tax_percentage' => (float) ($taxPercentage ?? 0),
            'rounding' => (float) ($rounding ?? 0),
        ], JSON_THROW_ON_ERROR));

        if (!array_key_exists($key, $cache)) {
            $cache[$key] = PurchaseOrder::calculateOrderBreakdown(
                $items,
                $discount,
                $taxType,
                $taxPercentage,
                $rounding,
            );
        }

        return $cache[$key];
    }

    protected static function getSummaryBreakdown(callable $get): array
    {
        $items = $get('purchaseOrderItems') ?? [];
        $discount = $get('discount');
        $taxType = $get('tax_type');
        $taxPercentage = $get('tax_percentage');
        $rounding = $get('rounding');

        return static::getOrderBreakdown(
            $items,
            $discount,
            $taxType,
            $taxPercentage,
            $rounding,
        )['summary'];
    }

    protected static function getCurrentLineBreakdown(callable $get): array
    {
        $items = $get('../../purchaseOrderItems') ?? [];
        $discount = $get('../../discount');
        $taxType = $get('../../tax_type');
        $taxPercentage = $get('../../tax_percentage');
        $rounding = $get('../../rounding');

        $lineKey = $get('line_key');
        $breakdown = static::getOrderBreakdown(
            $items,
            $discount,
            $taxType,
            $taxPercentage,
            $rounding,
        );

        if ($lineKey !== null && isset($breakdown['lines'][$lineKey])) {
            return $breakdown['lines'][$lineKey];
        }

        $sourceId = $get('purchase_request_item_id');

        return $breakdown['lines'][$sourceId] ?? [];
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
                // 'warehouse_address_id',
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
                // 'warehouse_address_id' => $firstPurchaseRequest->warehouse_address_id,
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
            // && $current->warehouse_address_id === $first->warehouse_address_id
        ;
    }

    protected static function fillHeaderFields(callable $set, ?array $header): void
    {
        $set('warehouse_id', $header['warehouse_id'] ?? null);
        $set('company_id', $header['company_id'] ?? null);
        $set('division_id', $header['division_id'] ?? null);
        $set('project_id', $header['project_id'] ?? null);
        // $set('warehouse_address_id', $header['warehouse_address_id'] ?? null);
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

    protected static function touchPaymentSummary(callable $set): void
    {
        $set('payment_summary_refresh_key', (string) str()->uuid());
    }

    protected static function formatMoney(float $amount): string
    {
        return 'Rp ' . number_format($amount, 2, ',', '.');
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
