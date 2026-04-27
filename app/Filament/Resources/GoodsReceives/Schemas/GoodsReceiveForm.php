<?php

namespace App\Filament\Resources\GoodsReceives\Schemas;

use App\Enums\GoodsReceiveType;
use App\Models\GoodsReceive;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
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
                        ])
                    ,
                    Grid::make()
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 1,
                        ])
                        ->columns(1)
                        ->schema([
                            static::infoSection(),
                            static::purchaseOrderInfoSection(),
                            static::vendorInfoSection(),
                        ])
                    ,
                ])
            ,
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
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->fontFamily(FontFamily::Mono)
                            ->columnSpan([
                                'default' => 2,
                                'lg' => 2,
                            ])
                            ->visibleOn('edit')
                        ,
                        // TextEntry::make('type')
                        //     ->hiddenLabel()
                        //     ->icon(fn($state) => $state?->icon())
                        //     ->formatStateUsing(fn($state) => $state?->label())
                        //     ->size(TextSize::Large)
                        //     ->color(fn($state) => $state?->color())
                        //     ->badge()
                        //     ->visibleOn('edit')
                        // ,

                        Select::make('type')
                            ->label(__('goods-receive.type.label'))
                            ->options(GoodsReceiveType::options())
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
                        Section::make(__('goods-receive.fieldset.warehouse_project.label'))
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
                                    ->disabled(fn($get, $operation) => $operation === 'edit' || filled($get('purchase_order_id')))
                                    ->dehydrated()
                                ,
                                Select::make('company_id')
                                    ->label(__('purchase-request.company.label'))
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
                                        $operation === 'edit' || blank($get('warehouse_id')) || filled($get('purchase_order_id'))
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
                                        $operation === 'edit' || blank($get('company_id')) || filled($get('purchase_order_id'))
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
                                        $operation === 'edit' || blank($get('warehouse_id')) || blank($get('company_id')) || filled($get('purchase_order_id'))
                                    )
                                    ->dehydrated()
                                ,

                                Select::make('warehouse_address_id')
                                    ->label(__('goods-receive.warehouse_address.label'))
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
                            ])
                        ,

                        Select::make('purchase_order_id')
                            ->label(__('purchase-order.model.label'))
                            ->relationship(
                                'purchaseOrder',
                                'number',
                                function (Builder $query, $get, ?GoodsReceive $record): Builder {
                                    $header = array_filter([
                                        'warehouse_id' => $get('warehouse_id'),
                                        'company_id' => $get('company_id'),
                                        'division_id' => $get('division_id'),
                                        'project_id' => $get('project_id'),
                                    ]);

                                    foreach ($header as $field => $value) {
                                        $query->where("purchase_orders.{$field}", $value);
                                    }

                                    $query->when(
                                        auth()->user()->warehouses()->exists(),
                                        fn(Builder $builder) => $builder->whereIn(
                                            'purchase_orders.warehouse_id',
                                            auth()->user()->warehouses->pluck('id'),
                                        ),
                                    );

                                    $selectableStatuses = GoodsReceive::SELECTABLE_PURCHASE_ORDER_STATUSES;

                                    $selectedIds = [];
                                    if ($record) {
                                        $selectedIds = $record->purchaseOrder()->pluck('purchase_orders.id')->all();
                                    }

                                    $query->where(function (Builder $scopedQuery) use ($selectableStatuses, $selectedIds): void {
                                        $scopedQuery->whereIn('status', $selectableStatuses);

                                        if ($selectedIds !== []) {
                                            $scopedQuery->orWhereIn('purchase_orders.id', $selectedIds);
                                        }
                                    });

                                    return $query->orderByDesc('purchase_orders.id');
                                }
                            )
                            ->searchable(['number', 'description'])
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $set): void {
                                $purchaseOrder = PurchaseOrder::query()
                                    ->with(['warehouse', 'company', 'division', 'project', 'warehouseAddress'])
                                    ->find((int) $state)
                                ;

                                $set('warehouse_id', $purchaseOrder?->warehouse_id ?? null);
                                $set('company_id', $purchaseOrder?->company_id ?? null);
                                $set('division_id', $purchaseOrder?->division_id ?? null);
                                $set('project_id', $purchaseOrder?->project_id ?? null);
                                $set('warehouse_address_id', $purchaseOrder?->warehouse_address_id ?? null);
                            })
                            ->dehydrated()
                            ->required(fn($get) => static::normalizeTypeState($get('type')) === GoodsReceiveType::PURCHASE_ORDER)
                            ->visible(fn($get) => static::normalizeTypeState($get('type')) === GoodsReceiveType::PURCHASE_ORDER)
                            ->disabledOn('edit')
                            ->columnSpanFull()
                        ,
                    ])
                ,
                Section::make(__('goods-receive.fieldset.main_info.label'))
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
                            ->label(__('common.description.label'))
                            ->placeholder(__('goods-receive.description.placeholder'))
                            ->helperText(__('goods-receive.description.helper'))
                            ->autosize()
                            ->live(debounce: 500)
                            ->columnSpanFull()
                        ,

                        TextInput::make('delivery_order')
                            ->label(__('goods-receive.delivery_order.label'))
                            ->placeholder(__('goods-receive.delivery_order.placeholder'))
                            ->helperText(__('goods-receive.delivery_order.helper'))
                            ->live(debounce: 500)
                            ->columnSpanFull()
                        ,
                    ])
                ,
            ])
        ;
    }

    protected static function itemSection(): Section|string
    {
        return Section::make(__('goods-receive.section.goods_receive_items.label'))
            ->icon(Heroicon::Cube)
            ->iconColor('primary')
            ->compact()
            ->schema([
                Repeater::make('goodsReceiveItems')
                    ->label(__('goods-receive.goods_receive_items.label'))
                    ->hiddenLabel()
                    ->relationship()
                    ->columns([
                        'lg' => 12,
                    ])
                    ->schema([
                        Select::make('purchase_order_item_id')
                            ->label(__('purchase-order.purchase_order_items.label'))
                            ->options(function ($get): array {
                                $type = static::normalizeTypeState($get('../../type'));
                                if ($type !== GoodsReceiveType::PURCHASE_ORDER) {
                                    return [];
                                }

                                $purchaseOrderId = (int) ($get('../../purchase_order_id') ?? 0);

                                return PurchaseOrderItem::getOptions($purchaseOrderId);
                            })
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search, $get): array {
                                $type = static::normalizeTypeState($get('../../type'));
                                if ($type !== GoodsReceiveType::PURCHASE_ORDER) {
                                    return [];
                                }

                                $purchaseOrderId = (int) ($get('../../purchase_order_id') ?? 0);

                                return PurchaseOrderItem::getOptions($purchaseOrderId);
                            })
                            ->preload()
                            ->hint(function ($get, $record): string {
                                $source = PurchaseOrderItem::findWithDetail((int) ($get('purchase_order_item_id') ?? 0));

                                if (!$source) {
                                    return '';
                                }

                                $goodsReceiveId = $record?->goods_receive_id;

                                return implode(' | ', array_filter([
                                    __('goods-receive.purchase_order_item.source_item.context_value', [
                                        'ordered_qty' => number_format((float) $source->qty, 2),
                                        'received_qty' => number_format((float) $source->getReceivedQty($goodsReceiveId), 2),
                                        'remaining_qty' => number_format((float) $source->getRemainingQty($goodsReceiveId), 2),
                                    ]),
                                ]));
                            })
                            ->live()
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

                                $source = PurchaseOrderItem::findWithDetail((int) $state);

                                if (!$source) {
                                    $set('item_id', null);
                                    $set('qty', null);
                                    $set('description', null);
                                    return;
                                }

                                $goodsReceiveId = $record?->goods_receive_id;

                                $set('item_id', $source->item_id);
                                $set('qty', $source->getRemainingQty($goodsReceiveId));
                                $set('description', $source->description);
                            })
                            ->disabled(
                                fn($operation, $get): bool =>
                                $operation === 'edit' || (static::normalizeTypeState($get('../../type')) !== GoodsReceiveType::PURCHASE_ORDER || blank($get('../../purchase_order_id')))
                            )
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->visible(fn($get): bool => static::normalizeTypeState($get('../../type')) === GoodsReceiveType::PURCHASE_ORDER)
                            ->columnSpanFull()
                        ,
                        Select::make('item_id')
                            ->label(__('item.code.label') . ' | ' . __('item.name.label'))
                            ->relationship('item', 'name')
                            ->getOptionLabelFromRecordUsing(fn($record): string => "{$record->code} | {$record->name}")
                            ->searchable(['code', 'name'])
                            ->required()
                            ->disabled(fn($get): bool => static::normalizeTypeState($get('../../type')) === GoodsReceiveType::PURCHASE_ORDER)
                            ->live()
                            ->dehydrated()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 8,
                            ])
                        ,

                        TextInput::make('qty')
                            ->numeric()
                            ->placeholder(0.01)
                            ->suffix(fn($get): string|null => Item::query()->whereKey($get('item_id'))->value('unit'))
                            ->minValue(0.01)
                            ->required()
                            ->live(debounce: 500)
                            ->rule(function ($get, $record) {
                                return function (string $attribute, $value, $fail) use ($get, $record): void {
                                    $sourceId = (int) $get('purchase_order_item_id');

                                    if ($sourceId <= 0) {
                                        return;
                                    }

                                    $source = PurchaseOrderItem::findWithDetail($sourceId);

                                    if (!$source) {
                                        return;
                                    }

                                    $goodsReceiveId = $record?->goods_receive_id;
                                    $remaining = $source->getRemainingQty($goodsReceiveId);

                                    if ((float) $value > $remaining) {
                                        $fail(__('goods-receive.validation.qty_exceeded', [
                                            'remaining' => number_format($remaining, 2),
                                        ]));
                                    }
                                };
                            })
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 4,
                            ])
                        ,
                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('goods-receive.goods_receive_item.description.placeholder'))
                            ->helperText(__('goods-receive.goods_receive_item.description.helper'))
                            ->autosize()
                            ->columnSpanFull()
                        ,
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
                    ->partiallyRenderAfterActionsCalled(false)
                ,
            ])
        ;
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
                    ->label(__('common.notes.label'))
                    ->placeholder(__('goods-receive.notes.placeholder'))
                    ->helperText(__('goods-receive.notes.helper'))
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
                    ->visible(fn($record, $operation) => $operation === 'edit')
                ,
                TextEntry::make('info')
                    ->label(__('purchase-order.revision_history.label'))
                    ->formatStateUsing(fn($state) => collect(explode("\n", $state))->map(fn($line) => "• " . e($line))->implode('<br>'))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->visible(fn($state, $record) => filled($state))
                ,
            ])
        ;
    }

    protected static function purchaseOrderInfoSection(): Section|string
    {
        return Section::make(__('purchase-order.section.main_info.label'))
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
                        ->state($purchaseOrder->number)
                        ->weight(FontWeight::Bold)
                        ->fontFamily(FontFamily::Mono)
                    ,
                    Grid::make()
                        ->schema([
                            TextEntry::make('purchase_order_status')
                                ->hiddenLabel()
                                ->icon($purchaseOrder->status->icon())
                                ->state($purchaseOrder->status->label())
                                ->color($purchaseOrder->status->color())
                                ->badge()
                            ,
                            TextEntry::make("purchase_order_created_at")
                                ->hiddenLabel()
                                ->icon(Heroicon::CalendarDays)
                                ->iconColor('primary')
                                ->state($purchaseOrder->created_at)
                                ->date()
                                ->alignEnd()
                            ,
                        ])
                    ,
                    TextEntry::make("purchase_order_description")
                        ->hiddenLabel()
                        ->state(nl2br($purchaseOrder->description))
                        ->html()
                        ->color('gray')
                        ->visible(fn($state) => filled($state))
                    ,
                    UserEntry::make("purchaseOrder.user")
                        ->hiddenLabel()
                        ->state($purchaseOrder->user)
                        ->color('gray')
                    ,
                ];
            })
        ;
    }

    protected static function vendorInfoSection(): Section|string
    {
        return Section::make(__('vendor.section.main_info.label'))
            ->icon(Heroicon::BuildingStorefront)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->visible(fn($get) => filled($get('purchase_order_id')))
            ->schema(function ($get) {
                $purchaseOrderId = (int) ($get('purchase_order_id') ?? 0);
                $purchaseOrder = static::getPurchaseOrderRecord($purchaseOrderId);
                $vendor = Vendor::find($purchaseOrder?->vendor_id);

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
}
