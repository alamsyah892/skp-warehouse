<?php

namespace App\Filament\Resources\GoodsReceives\Schemas;

use App\Enums\GoodsReceiveStatus;
use App\Enums\GoodsReceiveType;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\GoodsReceive;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
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
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Zvizvi\UserFields\Components\UserEntry;

class GoodsReceiveForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    'lg' => 4,
                ])
                ->dense()
                ->schema([
                    Grid::make()
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ])
                        ->columns(1)
                        ->schema([
                            static::dataSection(),
                            static::itemSection(),
                        ]),
                    Grid::make()
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 1,
                        ])
                        ->columns(1)
                        ->schema([
                            static::infoSection(),
                            static::purchaseOrderInfoSection(),
                        ]),
                ]),
        ]);
    }

    protected static function dataSection(): Section|string
    {
        return Section::make(__('goods-receive.section.main_info.label'))
            ->icon(Heroicon::InboxArrowDown)
            ->iconColor('primary')
            ->compact()
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
                            ->visibleOn('edit'),
                        // TextEntry::make('type')
                        //     ->hiddenLabel()
                        //     ->icon(fn($state) => $state?->icon())
                        //     ->formatStateUsing(fn($state) => $state?->label())
                        //     ->size(TextSize::Large)
                        //     ->color(fn($state) => $state?->color())
                        //     ->badge()
                        //     ->visibleOn('edit'),
                        Select::make('type')
                            ->label(__('goods-receive.type.label'))
                            ->options(GoodsReceiveType::options())
                            ->native(false)
                            ->required()
                            ->live()
                            ->columnSpanFull()
                            ->visibleOn('create'),
                    ]),
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
                            ->visibleOn('edit'),
                        TextEntry::make('created_at')
                            ->hiddenLabel()
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary')
                            ->date()
                            ->visibleOn('edit'),
                    ]),
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
                        Select::make('purchase_order_id')
                            ->label(__('goods-receive.purchase_order.label'))
                            ->relationship(
                                name: 'purchaseOrder',
                                titleAttribute: 'number',
                                modifyQueryUsing: fn(Builder $query) => $query->orderByDesc('id'),
                            )
                            ->searchable(['number', 'description'])
                            ->preload()
                            ->required(fn($get) => static::normalizeTypeState($get('type')) === GoodsReceiveType::PURCHASE_ORDER)
                            ->visible(fn($get) => static::normalizeTypeState($get('type')) === GoodsReceiveType::PURCHASE_ORDER)
                            ->live()
                            ->afterStateUpdated(function ($state, $set): void {
                                $purchaseOrder = static::getPurchaseOrderRecord((int) $state);
                                static::fillHeaderFieldsFromPurchaseOrder($set, $purchaseOrder);
                            })
                            ->afterStateHydrated(function ($state, $set, $get): void {
                                if (static::normalizeTypeState($get('type')) !== GoodsReceiveType::PURCHASE_ORDER) {
                                    return;
                                }

                                $purchaseOrder = static::getPurchaseOrderRecord((int) $state);
                                static::fillHeaderFieldsFromPurchaseOrder($set, $purchaseOrder);
                            })
                            ->columnSpanFull(),
                        Section::make(__('purchase-order.fieldset.warehouse_project.label'))
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
                                    ->disabled(fn($get, $operation) => $operation === 'edit' || static::shouldLockHeader($get))
                                    ->relationship(
                                        name: 'warehouse',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn($query) => $query->when(
                                            auth()->user()->warehouses()->exists(),
                                            fn($q) => $q->whereIn('warehouses.id', auth()->user()->warehouses->pluck('id')),
                                        )->orderBy('name')->orderBy('code'),
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
                                    ->dehydrated(),
                                Select::make('company_id')
                                    ->label(__('purchase-request.company.label'))
                                    ->disabled(fn($get, $operation) => $operation === 'edit' || blank($get('warehouse_id')) || static::shouldLockHeader($get))
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
                                    ->dehydrated(),
                                Select::make('division_id')
                                    ->label(__('division.model.label'))
                                    ->disabled(fn($get, $operation) => $operation === 'edit' || blank($get('company_id')) || static::shouldLockHeader($get))
                                    ->relationship(
                                        name: 'division',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: function ($query, $get) {
                                            $companyId = $get('company_id');

                                            $query
                                                ->when($companyId, function ($q) use ($companyId) {
                                                    $q
                                                        ->whereHas('companies', fn($qq) => $qq->where('companies.id', $companyId))
                                                        ->orWhereDoesntHave('companies');
                                                })
                                                ->orderBy('name')->orderBy('code');
                                        },
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->dehydrated(),
                                Select::make('project_id')
                                    ->label(__('project.model.label'))
                                    ->disabled(fn($get, $operation) => $operation === 'edit'
                                        || blank($get('warehouse_id'))
                                        || blank($get('company_id'))
                                        || static::shouldLockHeader($get))
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
                                                            ->orWhereDoesntHave('companies');
                                                    });
                                                })
                                                ->when($warehouseId, function ($q) use ($warehouseId) {
                                                    $q->where(function ($qq) use ($warehouseId) {
                                                        $qq
                                                            ->whereHas('warehouses', fn($q) => $q->where('warehouses.id', $warehouseId))
                                                            ->orWhereDoesntHave('warehouses');
                                                    });
                                                })
                                                ->where('allow_po', true)
                                                ->orderBy('name')->orderBy('code');
                                        },
                                    )
                                    ->searchable(['name', 'code', 'po_code'])
                                    ->preload()
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} / {$record->po_code} | {$record->name}")
                                    ->required()
                                    ->live()
                                    ->dehydrated(),
                                Select::make('warehouse_address_id')
                                    ->label(__('purchase-request.warehouse_address.label'))
                                    ->disabled(fn($get) => blank($get('warehouse_id')))
                                    ->relationship(
                                        name: 'warehouseAddress',
                                        titleAttribute: 'address',
                                        modifyQueryUsing: fn($query, $get) => $query->where('warehouse_id', $get('warehouse_id')),
                                    )
                                    ->searchable(['address', 'city'])
                                    ->preload()
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->address} - {$record->city}")
                                    ->default(null)
                                    ->live()
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make(__('purchase-order.fieldset.main_info.label'))
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
                        Textarea::make('description')
                            ->label(__('goods-receive.description.label'))
                            ->placeholder(__('goods-receive.description.placeholder'))
                            ->helperText(__('goods-receive.description.helper'))
                            ->autosize()
                            ->columnSpanFull(),
                        TextInput::make('delivery_order')
                            ->label(__('goods-receive.delivery_order.label'))
                            ->placeholder(__('goods-receive.delivery_order.placeholder'))
                            ->helperText(__('goods-receive.delivery_order.helper'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function itemSection(): Section|string
    {
        return Section::make(__('goods-receive.section.goods_receive_items.label'))
            ->icon(Heroicon::Cube)
            ->iconColor('primary')
            ->columnSpanFull()
            ->columns(1)
            ->compact()
            ->schema([
                Repeater::make('goodsReceiveItems')
                    ->label(__('goods-receive.goods_receive_items.label'))
                    ->hiddenLabel()
                    ->relationship()
                    ->mutateRelationshipDataBeforeCreateUsing(fn(array $data): array => Arr::except($data, ['line_key']))
                    ->mutateRelationshipDataBeforeSaveUsing(fn(array $data): array => Arr::except($data, ['line_key']))
                    ->columnSpanFull()
                    ->columns([
                        'lg' => 12,
                    ])
                    ->schema([
                        Hidden::make('line_key')
                            ->default(fn(): string => (string) str()->uuid())
                            ->dehydrated(),
                        Select::make('purchase_order_item_id')
                            ->label(__('goods-receive.purchase_order_item.label'))
                            ->options(function ($get): array {
                                $type = static::normalizeTypeState($get('../../type'));
                                if ($type !== GoodsReceiveType::PURCHASE_ORDER) {
                                    return [];
                                }

                                $purchaseOrderId = (int) ($get('../../purchase_order_id') ?? 0);

                                return $purchaseOrderId > 0
                                    ? static::getPurchaseOrderItemOptions($purchaseOrderId)
                                    : [];
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $record = static::getPurchaseOrderItemRecord((int) $value);
                                if (!$record) {
                                    return null;
                                }

                                $purchaseRequestNumber = $record->purchaseRequestItem?->purchaseRequest?->number;
                                $prefix = $purchaseRequestNumber ? "PR: {$purchaseRequestNumber} | " : '';

                                return $prefix . "{$record->item?->code} | {$record->item?->name}";
                            })
                            ->preload()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search, $get): array {
                                $type = static::normalizeTypeState($get('../../type'));
                                if ($type !== GoodsReceiveType::PURCHASE_ORDER) {
                                    return [];
                                }

                                $purchaseOrderId = (int) ($get('../../purchase_order_id') ?? 0);

                                return $purchaseOrderId > 0
                                    ? static::getPurchaseOrderItemOptions($purchaseOrderId, $search)
                                    : [];
                            })
                            ->disabled(fn($get): bool => static::normalizeTypeState($get('../../type')) !== GoodsReceiveType::PURCHASE_ORDER || blank($get('../../purchase_order_id')))
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->live()
                            ->hint(function ($get, $record): string {
                                $purchaseOrderItem = static::getPurchaseOrderItemRecord((int) ($get('purchase_order_item_id') ?? 0));

                                if (!$purchaseOrderItem) {
                                    return '';
                                }

                                $exceptGoodsReceiveId = $record?->goods_receive_id;

                                return implode(' | ', array_filter([
                                    __('purchase-order.purchase_order_item.source_item.context_value', [
                                        'request_qty' => number_format((float) $purchaseOrderItem->qty, 2),
                                        'ordered_qty' => number_format((float) $purchaseOrderItem->getReceivedQty($exceptGoodsReceiveId), 2),
                                        'remaining_qty' => number_format((float) $purchaseOrderItem->getRemainingReceiveQty($exceptGoodsReceiveId), 2),
                                    ]),
                                ]));
                            })
                            ->afterStateUpdated(function ($state, $set, $get, $record): void {
                                $type = static::normalizeTypeState($get('../../type'));

                                if ($type !== GoodsReceiveType::PURCHASE_ORDER) {
                                    return;
                                }

                                if (!$state) {
                                    $set('item_id', null);
                                    $set('qty', null);
                                    $set('description', null);
                                    return;
                                }

                                $source = static::getPurchaseOrderItemRecord((int) $state);

                                if (!$source) {
                                    $set('item_id', null);
                                    $set('qty', null);
                                    $set('description', null);
                                    return;
                                }

                                $exceptGoodsReceiveId = $record?->goods_receive_id;

                                $set('item_id', $source->item_id);
                                $set('qty', $source->getRemainingReceiveQty($exceptGoodsReceiveId));
                                $set('description', $source->description);
                            })
                            ->visible(fn($get): bool => static::normalizeTypeState($get('../../type')) === GoodsReceiveType::PURCHASE_ORDER)
                            ->columnSpanFull(),
                        Select::make('item_id')
                            ->label(__('item.related.code.label') . ' | ' . __('item.related.name.label'))
                            ->options(fn(): array => static::getItemOptions())
                            ->getOptionLabelUsing(function ($value): ?string {
                                $item = static::getItemRecord((int) $value);
                                return $item ? "{$item->code} | {$item->name}" : null;
                            })
                            ->preload()
                            ->searchable()
                            ->getSearchResultsUsing(fn(string $search): array => static::getItemOptions($search))
                            ->required()
                            ->live()
                            ->disabled(fn($get): bool => static::normalizeTypeState($get('../../type')) === GoodsReceiveType::PURCHASE_ORDER)
                            ->dehydrated()
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 6,
                            ]),
                        TextInput::make('qty')
                            ->label(__('goods-receive.qty.label'))
                            ->numeric()
                            ->minValue(0.01)
                            ->placeholder(__('goods-receive.qty.placeholder'))
                            ->required()
                            ->suffix(fn($get) => static::getItemUnit((int) ($get('item_id') ?? 0)))
                            ->live(debounce: 500)
                            ->rule(function ($get, $record) {
                                return function (string $attribute, $value, $fail) use ($get, $record): void {
                                    $type = static::normalizeTypeState($get('../../type'));

                                    if ($type !== GoodsReceiveType::PURCHASE_ORDER) {
                                        return;
                                    }

                                    $purchaseOrderItemId = (int) $get('purchase_order_item_id');

                                    if ($purchaseOrderItemId <= 0) {
                                        return;
                                    }

                                    $source = static::getPurchaseOrderItemRecord($purchaseOrderItemId);

                                    if (!$source) {
                                        return;
                                    }

                                    $goodsReceiveId = $record?->goods_receive_id;
                                    $remaining = $source->getRemainingReceiveQty($goodsReceiveId);

                                    if ((float) $value > $remaining) {
                                        $fail(__('goods-receive.validation.qty_exceeded', [
                                            'remaining' => number_format($remaining, 2),
                                        ]));
                                    }
                                };
                            })
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 2,
                            ]),
                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('purchase-order.purchase_order_item.description.placeholder'))
                            ->helperText(__('purchase-order.purchase_order_item.description.helper'))
                            ->autosize()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->reorderable()
                    ->orderColumn('sort')
                    ->itemLabel('#')
                    ->itemNumbers()
                    ->deleteAction(fn(Action $action) => $action->requiresConfirmation())
                    ->defaultItems(0)
                    ->minItems(1)
                    ->live()
                    ->partiallyRenderAfterActionsCalled(false),
            ]);
    }

    protected static function infoSection(): Section|string
    {
        return Section::make(__('goods-receive.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->schema([
                Textarea::make('notes')
                    ->label(__('goods-receive.notes.label'))
                    ->placeholder(__('goods-receive.notes.placeholder'))
                    ->helperText(__('goods-receive.notes.helper'))
                    ->autosize()
                    ->columnSpanFull(),
                UserEntry::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->color('gray')
                    ->visibleOn('edit'),
                TextEntry::make('updated_at')
                    ->date()
                    ->label(__('common.updated_at.label'))
                    ->size(TextSize::Small)
                    ->color('gray')
                    ->visibleOn('edit'),
                TextEntry::make('deleted_at')
                    ->date()
                    ->label(__('common.deleted_at.label'))
                    ->size(TextSize::Small)
                    ->color('gray')
                    ->visible(fn($state) => $state != null),
                Textarea::make('info')
                    ->label(__('goods-receive.info.label'))
                    ->placeholder(__('goods-receive.info.placeholder'))
                    ->helperText(__('goods-receive.info.helper'))
                    ->autosize()
                    ->required(fn($get, $record) => $record?->hasWatchedFieldChanges($get()) === true)
                    ->disabled(fn($get, $record) => $record?->hasWatchedFieldChanges($get()) === false)
                    ->afterStateHydrated(fn($component) => $component->state(null))
                    ->visible(fn($record, $operation) => $operation === 'edit' && !$record?->hasStatus(GoodsReceiveStatus::RECEIVED)),
                TextEntry::make('info')
                    ->label(__('purchase-order.revision_history.label'))
                    ->formatStateUsing(fn($state) => collect(explode("\n", (string) $state))->map(fn($line) => '• ' . e($line))->implode('<br>'))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->visible(fn($state, $record) => filled($state) && !$record?->hasStatus(GoodsReceiveStatus::RECEIVED)),
            ]);
    }

    protected static function purchaseOrderInfoSection(): Section|string
    {
        return Section::make(__('goods-receive.purchase_order.label'))
            ->icon(Heroicon::ShoppingCart)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->visible(fn($get) => filled($get('purchase_order_id')))
            ->schema(function ($get) {
                $purchaseOrderId = (int) ($get('purchase_order_id') ?? 0);
                $purchaseOrder = static::getPurchaseOrderRecord($purchaseOrderId);

                if (!$purchaseOrder) {
                    return [];
                }

                return [
                    TextEntry::make('purchase_order_number')
                        ->hiddenLabel()
                        ->icon(Heroicon::Hashtag)
                        ->iconColor('primary')
                        ->fontFamily(FontFamily::Mono)
                        ->weight(FontWeight::Bold)
                        ->state($purchaseOrder->number)
                        ->url(fn(): string => PurchaseOrderResource::getUrl('view', ['record' => $purchaseOrder->id])),
                    TextEntry::make('purchase_order_description')
                        ->hiddenLabel()
                        ->state(nl2br(e((string) $purchaseOrder->description)))
                        ->html()
                        ->size(TextSize::Small)
                        ->color('gray')
                        ->visible(fn($state) => filled($state)),
                ];
            });
    }

    protected static function normalizeTypeState(mixed $state): ?GoodsReceiveType
    {
        if ($state instanceof GoodsReceiveType) {
            return $state;
        }

        if (blank($state)) {
            return null;
        }

        return GoodsReceiveType::tryFrom((int) $state);
    }

    protected static function shouldLockHeader(callable $get): bool
    {
        return static::normalizeTypeState($get('type')) === GoodsReceiveType::PURCHASE_ORDER
            && filled($get('purchase_order_id'));
    }

    protected static function getPurchaseOrderRecord(?int $purchaseOrderId): ?PurchaseOrder
    {
        if (!$purchaseOrderId) {
            return null;
        }

        static $cache = [];

        if (!array_key_exists($purchaseOrderId, $cache)) {
            $cache[$purchaseOrderId] = PurchaseOrder::query()
                ->with(['warehouse', 'company', 'division', 'project', 'warehouseAddress'])
                ->find($purchaseOrderId);
        }

        return $cache[$purchaseOrderId];
    }

    protected static function fillHeaderFieldsFromPurchaseOrder(callable $set, ?PurchaseOrder $purchaseOrder): void
    {
        if (!$purchaseOrder) {
            return;
        }

        $set('warehouse_id', $purchaseOrder->warehouse_id);
        $set('company_id', $purchaseOrder->company_id);
        $set('division_id', $purchaseOrder->division_id);
        $set('project_id', $purchaseOrder->project_id);
        $set('warehouse_address_id', $purchaseOrder->warehouse_address_id);
    }

    protected static function getPurchaseOrderItemRecord(?int $purchaseOrderItemId): ?PurchaseOrderItem
    {
        if (!$purchaseOrderItemId) {
            return null;
        }

        static $cache = [];

        if (!array_key_exists($purchaseOrderItemId, $cache)) {
            $cache[$purchaseOrderItemId] = PurchaseOrderItem::query()
                ->with([
                    'item',
                    'purchaseRequestItem.purchaseRequest',
                ])
                ->find($purchaseOrderItemId);
        }

        return $cache[$purchaseOrderItemId];
    }

    protected static function getPurchaseOrderItemOptions(int $purchaseOrderId, ?string $search = null): array
    {
        static $cache = [];

        $cacheKey = md5(json_encode([
            'purchase_order_id' => $purchaseOrderId,
            'search' => filled($search) ? trim($search) : null,
        ], JSON_THROW_ON_ERROR));

        if (!array_key_exists($cacheKey, $cache)) {
            $query = PurchaseOrderItem::query()
                ->where('purchase_order_id', $purchaseOrderId)
                ->with([
                    'item:id,code,name',
                    'purchaseRequestItem.purchaseRequest:id,number',
                ])
                ->orderBy('sort');

            if (filled($search)) {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->whereHas('item', function (Builder $itemQuery) use ($search): void {
                            $itemQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('purchaseRequestItem.purchaseRequest', function (Builder $prQuery) use ($search): void {
                            $prQuery->where('number', 'like', "%{$search}%");
                        });
                });
            }

            $cache[$cacheKey] = $query
                ->limit(50)
                ->get()
                ->mapWithKeys(function (PurchaseOrderItem $record): array {
                    $purchaseRequestNumber = $record->purchaseRequestItem?->purchaseRequest?->number;
                    $prefix = $purchaseRequestNumber ? "PR: {$purchaseRequestNumber} | " : '';

                    return [
                        $record->id => $prefix . "{$record->item?->code} | {$record->item?->name}",
                    ];
                })
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
            $cache[$itemId] = Item::query()->find($itemId);
        }

        return $cache[$itemId];
    }

    protected static function getItemOptions(?string $search = null): array
    {
        static $cache = [];

        $cacheKey = md5(json_encode([
            'search' => filled($search) ? trim($search) : null,
        ], JSON_THROW_ON_ERROR));

        if (!array_key_exists($cacheKey, $cache)) {
            $query = Item::query()->orderBy('code')->orderBy('name');

            if (filled($search)) {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }

            $cache[$cacheKey] = $query
                ->limit(50)
                ->get(['id', 'code', 'name'])
                ->mapWithKeys(fn(Item $item): array => [
                    $item->id => "{$item->code} | {$item->name}",
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
}
