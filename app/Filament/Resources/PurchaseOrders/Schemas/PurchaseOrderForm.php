<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
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
use Filament\Schemas\Schema;
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
            ->columns(2)
            ->compact()
            ->schema([
                Fieldset::make(__('purchase-order.fieldset.warehouse_project.label'))
                    ->columns(1)
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
                            })
                        ,
                    ])
                ,

                Fieldset::make(__('purchase-order.fieldset.info.label'))
                    ->columns(1)
                    ->schema([
                        TextInput::make('number')
                            ->label(__('purchase-order.number.label'))
                            ->hint('Auto-generated')
                            ->hintIcon('heroicon-m-information-circle')
                            ->readOnly()
                            ->visibleOn('edit')
                            ->dehydrated(false),

                        Select::make('vendor_id')
                            ->label(__('vendor.model.label'))
                            ->relationship('vendor', 'name', fn($query) => $query->orderBy('name')->orderBy('code'))
                            ->searchable(['name', 'code'])
                            ->required()
                            ->preload()
                        ,
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
                        ,

                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('purchase-order.description.placeholder'))
                            ->helperText(__('purchase-order.description.helper'))
                            ->live()
                            ->autosize()
                            ->columnSpanFull(),
                        TextInput::make('termin')
                            ->placeholder(__('purchase-order.termin.placeholder'))
                            ->helperText(__('purchase-order.termin.helper'))
                        ,
                        TextInput::make('delivery_info')
                            ->placeholder(__('purchase-order.delivery_info.placeholder'))
                            ->helperText(__('purchase-order.delivery_info.helper'))
                        ,

                        Select::make('status')
                            ->options(fn($record) => $record->getAvailableStatusOptions())
                            ->native(false)
                            ->required()
                            ->disableOptionWhen(function ($value, $record) {
                                if (!$record) {
                                    return false;
                                }

                                return !$record->canChangeStatusTo($value);
                            })
                            ->visibleOn('edit'),
                    ]),
            ]);
    }

    protected static function itemSection(): Section
    {
        return Section::make('Form ' . __('purchase-order.section.purchase_order_items.label'))
            ->icon(Heroicon::OutlinedCube)
            ->iconColor('primary')
            ->description(__('purchase-order.section.purchase_order_items.description'))
            ->collapsible()
            ->columnSpanFull()
            ->columns(1)
            ->compact()
            ->schema([
                Repeater::make('purchaseOrderItems')
                    ->label(__('purchase-order.purchase_order_items.label'))
                    ->hiddenLabel()
                    ->relationship()
                    ->columnSpanFull()
                    ->columns(6)
                    ->schema([
                        Select::make('purchase_request_item_id')
                            ->label(
                                __('item.related.code.label') .
                                ' | ' .
                                __('item.related.name.label') .
                                ' | ' .
                                __('purchase-order.purchase_requests.number.label')
                            )
                            ->options(function ($get): array {
                                $purchaseRequestIds = PurchaseOrder::normalizePurchaseRequestIds((array) ($get('../../purchaseRequests') ?? []));

                                if (blank($purchaseRequestIds)) {
                                    return [];
                                }

                                return PurchaseOrder::getCompatiblePurchaseRequestItemsQuery($purchaseRequestIds)
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn(PurchaseRequestItem $record) => [
                                        $record->id => "{$record->item?->code} | {$record->item?->name} | {$record->purchaseRequest?->number}",
                                    ])
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search, $get): array {
                                $purchaseRequestIds = PurchaseOrder::normalizePurchaseRequestIds((array) ($get('../../purchaseRequests') ?? []));

                                if (blank($purchaseRequestIds)) {
                                    return [];
                                }

                                return PurchaseOrder::getCompatiblePurchaseRequestItemsQuery($purchaseRequestIds)
                                    ->where(function (Builder $query) use ($search): void {
                                        $query->whereHas('item', function (Builder $itemQuery) use ($search): void {
                                            $itemQuery
                                                ->where('name', 'like', "%{$search}%")
                                                ->orWhere('code', 'like', "%{$search}%");
                                        })
                                            ->orWhereHas('purchaseRequest', function (Builder $purchaseRequestQuery) use ($search): void {
                                                $purchaseRequestQuery->where('number', 'like', "%{$search}%");
                                            });
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn(PurchaseRequestItem $record) => [
                                        $record->id => "{$record->item?->code} | {$record->item?->name} | {$record->purchaseRequest?->number}",
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $record = PurchaseRequestItem::with(['item', 'purchaseRequest'])->find($value);

                                if (!$record) {
                                    return null;
                                }

                                return "{$record->item?->code} | {$record->item?->name} | {$record->purchaseRequest?->number}";
                            })
                            // ->preload()
                            ->searchable()
                            ->live()
                            ->disabled(fn($get): bool => blank(PurchaseOrder::normalizePurchaseRequestIds((array) ($get('../../purchaseRequests') ?? []))))
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->required()
                            ->columnSpan(2)
                            ->afterStateUpdated(function ($state, $set): void {
                                if (!$state) {
                                    $set('item_id', null);
                                    $set('qty', null);
                                    // $set('discount', 0);
                    
                                    return;
                                }

                                $source = PurchaseRequestItem::query()
                                    ->with(['item', 'purchaseRequest'])
                                    ->find($state);

                                if (!$source) {
                                    return;
                                }

                                $set('item_id', $source->item_id);
                                $set('qty', $source->getRemainingQty());
                                // $set('discount', (float) $source->discount);
                            }),
                        Hidden::make('item_id')->required(),
                        TextInput::make('qty')
                            ->label(__('purchase-order.purchase_order_item.qty.label'))
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->live()
                            ->suffix(function ($get) {
                                $source = PurchaseRequestItem::query()->with('item:id,unit')->find($get('purchase_request_item_id'));

                                return $source?->item?->unit;
                            })
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
                            ->columnSpan(1),
                        TextInput::make('price')
                            ->label(__('purchase-order.purchase_order_item.price.label'))
                            ->numeric()
                            ->minValue(0)
                            // ->default(0)
                            ->required()
                            ->live()
                            ->columnSpan(1),
                        TextInput::make('discount')
                            ->label(__('purchase-order.purchase_order_item.discount.label'))
                            ->numeric()
                            // ->default(0)
                            ->dehydrated()
                            ->columnSpan(1),
                        TextEntry::make('line_total')
                            ->label(__('purchase-order.purchase_order_item.total.label'))
                            ->state(fn($get): string => static::formatMoney(
                                PurchaseOrder::calculateItemTotal([
                                    'qty' => $get('qty'),
                                    'price' => $get('price'),
                                    'discount' => $get('discount'),
                                ])
                            ))
                            ->columnSpan(1),
                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('purchase-order.purchase_order_item.description.placeholder'))
                            ->helperText(__('purchase-order.purchase_order_item.description.helper'))
                            ->autosize()
                            ->columnSpanFull(),
                        TextEntry::make('source_context')
                            ->label(__('purchase-order.purchase_order_item.source_item.context'))
                            ->columnSpanFull()
                            ->state(function ($get): string {
                                $sourceId = $get('purchase_request_item_id');

                                if (!$sourceId) {
                                    return '-';
                                }

                                $source = PurchaseRequestItem::query()->with(['item', 'purchaseRequest'])->find($sourceId);

                                if (!$source) {
                                    return '-';
                                }

                                return __('purchase-order.purchase_order_item.source_item.context_value', [
                                    'number' => $source->purchaseRequest?->number ?? '-',
                                    'code' => $source->item?->code ?? '-',
                                    'name' => $source->item?->name ?? '-',
                                    'request_qty' => number_format((float) $source->qty, 2),
                                    'ordered_qty' => number_format($source->getOrderedQty(), 2),
                                    'remaining_qty' => number_format($source->getRemainingQty(), 2),
                                    'discount' => static::formatMoney((float) $source->discount),
                                ]);
                            })
                            ->color('gray')
                            ->size(TextSize::Small)
                            ->columnSpanFull()
                        ,
                    ])
                    ->reorderable()
                    ->orderColumn('sort')
                    ->itemNumbers()
                    ->deleteAction(fn(Action $action) => $action->requiresConfirmation())
                    ->defaultItems(0)
                    ->minItems(1)
                    ->live()
                ,
            ]);
    }

    protected static function totalSection(): Section
    {
        return Section::make('Ringkasan Total')
            ->icon(Heroicon::Calculator)
            ->iconColor('primary')
            ->collapsible()
            ->columnSpanFull()
            ->compact()
            ->schema([
                Grid::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('subtotal_display')
                            ->label(__('purchase-order.total.subtotal'))
                            ->state(fn($get): string => static::formatMoney(
                                PurchaseOrder::calculateSubtotal($get('purchaseOrderItems') ?? [])
                            ))
                            ->columnSpanFull()
                        ,
                        TextInput::make('discount')
                            ->label(__('purchase-order.total.discount'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('Rp')
                            ->live(),
                        TextEntry::make('net_subtotal_display')
                            ->label(__('purchase-order.total.net_subtotal'))
                            ->state(fn($get): string => static::formatMoney(
                                PurchaseOrder::calculateNetSubtotal(
                                    $get('purchaseOrderItems') ?? [],
                                    $get('discount')
                                )
                            )),
                        TextInput::make('tax')
                            ->label(__('purchase-order.total.tax'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('Rp')
                            ->live(),
                        TextInput::make('tax_description')
                            ->label(__('purchase-order.total.tax_description'))
                            ->placeholder(__('purchase-order.total.tax_description_placeholder'))
                        ,
                        TextInput::make('rounding')
                            ->label(__('purchase-order.total.rounding'))
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            ->live(),
                        TextEntry::make('grand_total_display')
                            ->label(__('purchase-order.total.grand_total'))
                            ->state(fn($get): string => static::formatMoney(
                                PurchaseOrder::calculateGrandTotal(
                                    $get('purchaseOrderItems') ?? [],
                                    $get('discount'),
                                    $get('tax'),
                                    $get('rounding'),
                                )
                            ))
                        ,
                    ]),
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
            $set('purchaseOrderItems', []);

            return;
        }

        $allowedSourceIds = PurchaseRequestItem::query()
            ->whereIn('purchase_request_id', $selectedPurchaseRequestIds)
            ->pluck('id')
            ->map(fn($id): int => (int) $id)
            ->all();

        $filteredItems = $items
            ->filter(fn(array $item): bool => in_array((int) ($item['purchase_request_item_id'] ?? 0), $allowedSourceIds, true))
            ->values()
            ->all();

        if ($filteredItems !== $items->values()->all()) {
            $set('purchaseOrderItems', $filteredItems);
        }
    }

    protected static function formatMoney(float $amount): string
    {
        return 'Rp ' . number_format($amount, 2, ',', '.');
    }
}
