<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\PurchaseOrderTaxType;
use App\Models\GoodsReceive;
use App\Models\GoodsReceiveItem;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
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

class InvoiceForm
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
                            static::summaryTotalSection(),
                        ]),

                    Grid::make()
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 1,
                        ])
                        ->columns(1)
                        ->schema([
                            static::infoSection(),
                            static::goodsReceiveInfoSection(),
                            static::purchaseOrderInfoSection(),
                            static::vendorInfoSection(),
                        ]),
                ]),
        ]);
    }

    protected static function dataSection(): Section
    {
        return Section::make('Data Invoice')
            ->icon(Heroicon::DocumentText)
            ->iconColor('primary')
            ->compact()
            ->columns([
                'default' => 1,
                'lg' => 2,
            ])
            ->schema([
                Hidden::make('purchase_order_id')
                    ->dehydrated(),

                Select::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name', fn(Builder $query) => $query->orderBy('name')->orderBy('code'))
                    ->searchable(['name', 'code'])
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($set): void {
                        $set('goods_receive_id', null);
                        $set('purchase_order_id', null);
                        $set('invoiceItems', []);
                    })
                    ->required(),

                Select::make('goods_receive_id')
                    ->label('Goods Receive')
                    ->relationship(
                        'goodsReceive',
                        'number',
                        function (Builder $query, $get, ?Invoice $record): Builder {
                            $vendorId = (int) ($get('vendor_id') ?? 0);
                            $selectedId = $record?->goods_receive_id;

                            return $query
                                ->whereHas('purchaseOrder', function (Builder $purchaseOrderQuery) use ($vendorId): void {
                                    if ($vendorId > 0) {
                                        $purchaseOrderQuery->where('vendor_id', $vendorId);
                                    } else {
                                        $purchaseOrderQuery->whereRaw('1 = 0');
                                    }
                                })
                                ->when(
                                    $selectedId,
                                    fn(Builder $builder): Builder => $builder->orWhere('goods_receives.id', $selectedId),
                                )
                                ->orderByDesc('goods_receives.id');
                        },
                    )
                    ->getOptionLabelFromRecordUsing(fn(GoodsReceive $record): string => "{$record->number} | {$record->purchaseOrder?->number}")
                    ->searchable(['number', 'delivery_order', 'description'])
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, $set): void {
                        $goodsReceive = static::getGoodsReceiveRecord((int) $state);

                        if (!$goodsReceive) {
                            $set('purchase_order_id', null);
                            $set('invoiceItems', []);

                            return;
                        }

                        $set('purchase_order_id', $goodsReceive->purchase_order_id);
                        $set('invoiceItems', static::mapGoodsReceiveItemsForInvoice($goodsReceive));
                    })
                    ->disabled(fn($get): bool => blank($get('vendor_id')))
                    ->required()
                    ->dehydrated(),

                TextInput::make('number')
                    ->label('Nomor Invoice')
                    ->maxLength(255)
                    ->required()
                    ->unique(ignoreRecord: true),

                DatePicker::make('invoice_date')
                    ->label('Tanggal Invoice')
                    ->native(false)
                    ->live()
                    ->default(now())
                    ->required(),

                DatePicker::make('invoice_due_date')
                    ->label('Tanggal Jatuh Tempo')
                    ->native(false)
                    ->minDate(fn($get) => $get('invoice_date'))
                    ->required(),
            ]);
    }

    protected static function itemSection(): Section
    {
        return Section::make('Item Invoice')
            ->icon(Heroicon::QueueList)
            ->iconColor('primary')
            ->compact()
            ->schema([
                Repeater::make('invoiceItems')
                    ->label('Invoice Items')
                    ->relationship()
                    ->columns([
                        'default' => 1,
                        'lg' => 12,
                    ])
                    ->schema([
                        Select::make('goods_receive_item_id')
                            ->label('Item Goods Receive')
                            ->options(fn($get): array => static::getGoodsReceiveItemOptions((int) ($get('../../goods_receive_id') ?? 0)))
                            ->getSearchResultsUsing(fn(string $search, $get): array => static::getGoodsReceiveItemOptions(
                                (int) ($get('../../goods_receive_id') ?? 0),
                                $search,
                            ))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $set): void {
                                $source = static::getGoodsReceiveItemRecord((int) $state);

                                if (!$source) {
                                    $set('purchase_order_item_id', null);
                                    $set('item_id', null);
                                    $set('qty', null);
                                    $set('price', null);
                                    $set('description', null);

                                    return;
                                }

                                $set('purchase_order_item_id', $source->purchase_order_item_id);
                                $set('item_id', $source->item_id);
                                $set('qty', $source->qty);
                                $set('price', $source->purchaseOrderItem?->price ?? 0);
                                $set('description', $source->description);
                            })
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->disabled(fn($get): bool => blank($get('../../goods_receive_id')))
                            ->required()
                            ->columnSpanFull(),

                        Select::make('item_id')
                            ->label('Kode | Nama Item')
                            ->relationship('item', 'name')
                            ->getOptionLabelFromRecordUsing(fn(Item $record): string => "{$record->code} | {$record->name}")
                            ->searchable(['code', 'name'])
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 5,
                            ]),

                        TextInput::make('qty')
                            ->label('Qty')
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->suffix(fn($get): ?string => Item::query()->whereKey($get('item_id'))->value('unit'))
                            ->live(debounce: 500)
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),

                        TextInput::make('price')
                            ->label('Harga')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->required()
                            ->live(debounce: 500)
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 3,
                            ]),

                        TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->state(fn($get): float => (float) ($get('qty') ?? 0) * (float) ($get('price') ?? 0))
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->alignEnd()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),

                        Textarea::make('description')
                            ->label('Keterangan')
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

    protected static function summaryTotalSection(): Section
    {
        return Section::make('Ringkasan Total')
            ->icon(Heroicon::Calculator)
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
                        'default' => 1,
                        'lg' => 4,
                    ])
                    ->schema([
                        Select::make('tax_type')
                            ->label('Tipe Pajak')
                            ->options(PurchaseOrderTaxType::options())
                            ->default(PurchaseOrderTaxType::EXCLUDE->value)
                            ->native(false)
                            ->live()
                            ->required(),

                        Select::make('tax_percentage')
                            ->label('Persentase Pajak')
                            ->options(PurchaseOrder::getTaxPercentageOptions())
                            ->default(0)
                            ->native(false)
                            ->live()
                            ->required(),

                        TextInput::make('discount')
                            ->label('Diskon')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            ->live(debounce: 500),

                        TextInput::make('rounding')
                            ->label('Pembulatan')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp')
                            ->live(debounce: 500),
                    ]),

                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->columns(2)
                    ->schema([
                        TextEntry::make('total_subtotal')
                            ->label('Subtotal')
                            ->state(fn($get): float => static::getSummaryTotals($get)['subtotal'])
                            ->numeric()
                            ->alignEnd(),

                        TextEntry::make('total_discount')
                            ->label('Diskon')
                            ->state(fn($get): float => static::getSummaryTotals($get)['discount'])
                            ->numeric()
                            ->alignEnd(),

                        TextEntry::make('total_after_discount')
                            ->label('Setelah Diskon')
                            ->state(fn($get): float => static::getSummaryTotals($get)['subtotal_after_discount'])
                            ->numeric()
                            ->alignEnd(),

                        TextEntry::make('total_tax')
                            ->label(fn($get): string => 'Pajak' . (filled($get('tax_percentage')) ? " ({$get('tax_percentage')}%)" : ''))
                            // ->state(fn ($get): float => static::getSummaryTotals($get)['tax'])
                            ->numeric()
                            ->alignEnd(),

                        TextEntry::make('total_rounding')
                            ->label('Pembulatan')
                            ->state(fn($get): float => (float) ($get('rounding') ?? 0))
                            ->numeric()
                            ->alignEnd(),

                        TextEntry::make('total_grand_total')
                            ->label('Grand Total')
                            ->state(fn($get): float => static::getSummaryTotals($get)['grand_total'])
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->color('primary')
                            ->alignEnd(),
                    ]),
            ]);
    }

    protected static function infoSection(): Section
    {
        return Section::make('Info Lain')
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->schema([
                Textarea::make('notes')
                    ->label('Catatan')
                    ->autosize(),

                UserEntry::make('user')
                    ->label('Dibuat oleh')
                    ->color('gray')
                    ->visibleOn('edit'),

                TextEntry::make('updated_at')
                    ->label('Diperbarui')
                    ->date()
                    ->size(TextSize::Small)
                    ->color('gray')
                    ->visibleOn('edit'),

                TextEntry::make('deleted_at')
                    ->label('Dihapus')
                    ->date()
                    ->size(TextSize::Small)
                    ->color('gray')
                    ->visible(fn($state): bool => $state !== null),

                Textarea::make('info')
                    ->label('Info')
                    ->autosize()
                    ->visibleOn('edit'),
            ]);
    }

    protected static function goodsReceiveInfoSection(): Section
    {
        return Section::make('Goods Receive')
            ->icon(Heroicon::InboxArrowDown)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->visible(fn($get): bool => filled($get('goods_receive_id')))
            ->schema(function ($get): array {
                $goodsReceive = static::getGoodsReceiveRecord((int) ($get('goods_receive_id') ?? 0));

                if (!$goodsReceive) {
                    return [];
                }

                return [
                    TextEntry::make('goods_receive_number')
                        ->hiddenLabel()
                        ->icon(Heroicon::Hashtag)
                        ->iconColor('primary')
                        ->state($goodsReceive->number)
                        ->weight(FontWeight::Bold)
                        ->fontFamily(FontFamily::Mono),

                    Grid::make()
                        ->columns(2)
                        ->schema([
                            TextEntry::make('goods_receive_delivery_order')
                                ->hiddenLabel()
                                ->icon(Heroicon::Truck)
                                ->iconColor('primary')
                                ->state($goodsReceive->delivery_order)
                                ->placeholder('-'),

                            TextEntry::make('goods_receive_created_at')
                                ->hiddenLabel()
                                ->icon(Heroicon::CalendarDays)
                                ->iconColor('primary')
                                ->state($goodsReceive->created_at)
                                ->date()
                                ->alignEnd(),
                        ]),

                    TextEntry::make('goods_receive_description')
                        ->hiddenLabel()
                        ->state(nl2br(e($goodsReceive->description)))
                        ->html()
                        ->color('gray')
                        ->visible(fn($state): bool => filled($state)),
                ];
            });
    }

    protected static function purchaseOrderInfoSection(): Section
    {
        return Section::make('Purchase Order')
            ->icon(Heroicon::ShoppingCart)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->visible(fn($get): bool => filled($get('purchase_order_id')))
            ->schema(function ($get): array {
                $purchaseOrder = static::getPurchaseOrderRecord((int) ($get('purchase_order_id') ?? 0));

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
                        ->fontFamily(FontFamily::Mono),

                    TextEntry::make('purchase_order_description')
                        ->hiddenLabel()
                        ->state(nl2br(e($purchaseOrder->description)))
                        ->html()
                        ->color('gray')
                        ->visible(fn($state): bool => filled($state)),
                ];
            });
    }

    protected static function vendorInfoSection(): Section
    {
        return Section::make('Vendor')
            ->icon(Heroicon::BuildingStorefront)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->visible(fn($get): bool => filled($get('vendor_id')))
            ->schema(function ($get): array {
                $vendor = Vendor::query()->find((int) ($get('vendor_id') ?? 0));

                if (!$vendor) {
                    return [];
                }

                return [
                    TextEntry::make('vendor_name')
                        ->hiddenLabel()
                        ->icon(Heroicon::BuildingStorefront)
                        ->iconColor('primary')
                        ->state($vendor->name)
                        ->weight(FontWeight::Bold),

                    TextEntry::make('vendor_address')
                        ->hiddenLabel()
                        ->icon(Heroicon::MapPin)
                        ->iconColor('primary')
                        ->state(collect([$vendor->address, $vendor->city])->filter()->join(' - '))
                        ->size(TextSize::Small)
                        ->color('gray')
                        ->visible(fn($state): bool => filled($state)),

                    TextEntry::make('vendor_contact_person')
                        ->hiddenLabel()
                        ->icon(Heroicon::UserCircle)
                        ->iconColor('primary')
                        ->state($vendor->contact_person)
                        ->size(TextSize::Small)
                        ->color('gray')
                        ->visible(fn($state): bool => filled($state)),
                ];
            });
    }

    /**
     * @return array<int|string, mixed>
     */
    protected static function getSummaryTotals(callable $get): array
    {
        return Invoice::calculateSummary(
            (array) ($get('invoiceItems') ?? []),
            (float) ($get('discount') ?? 0),
            $get('tax_type'),
            $get('tax_percentage') ?? 0,
            (float) ($get('rounding') ?? 0),
        );
    }

    protected static function getGoodsReceiveRecord(?int $goodsReceiveId): ?GoodsReceive
    {
        if (!$goodsReceiveId) {
            return null;
        }

        static $cache = [];

        if (!array_key_exists($goodsReceiveId, $cache)) {
            $cache[$goodsReceiveId] = GoodsReceive::query()
                ->with(['purchaseOrder.vendor', 'goodsReceiveItems.item', 'goodsReceiveItems.purchaseOrderItem'])
                ->withTrashed()
                ->find($goodsReceiveId);
        }

        return $cache[$goodsReceiveId];
    }

    protected static function getPurchaseOrderRecord(?int $purchaseOrderId): ?PurchaseOrder
    {
        if (!$purchaseOrderId) {
            return null;
        }

        static $cache = [];

        if (!array_key_exists($purchaseOrderId, $cache)) {
            $cache[$purchaseOrderId] = PurchaseOrder::query()
                ->with(['vendor'])
                ->withTrashed()
                ->find($purchaseOrderId);
        }

        return $cache[$purchaseOrderId];
    }

    protected static function getGoodsReceiveItemRecord(?int $goodsReceiveItemId): ?GoodsReceiveItem
    {
        if (!$goodsReceiveItemId) {
            return null;
        }

        static $cache = [];

        if (!array_key_exists($goodsReceiveItemId, $cache)) {
            $cache[$goodsReceiveItemId] = GoodsReceiveItem::query()
                ->with(['item', 'purchaseOrderItem'])
                ->find($goodsReceiveItemId);
        }

        return $cache[$goodsReceiveItemId];
    }

    protected static function getGoodsReceiveItemOptions(int $goodsReceiveId, ?string $search = null): array
    {
        if ($goodsReceiveId <= 0) {
            return [];
        }

        return GoodsReceiveItem::query()
            ->where('goods_receive_id', $goodsReceiveId)
            ->with(['item', 'purchaseOrderItem'])
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->whereHas('item', function (Builder $itemQuery) use ($search): void {
                    $itemQuery
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn(GoodsReceiveItem $record): array => [
                $record->id => "{$record->item?->code} | {$record->item?->name} ({$record->qty})",
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function mapGoodsReceiveItemsForInvoice(GoodsReceive $goodsReceive): array
    {
        return $goodsReceive->goodsReceiveItems
            ->map(fn(GoodsReceiveItem $item): array => [
                'goods_receive_item_id' => $item->id,
                'purchase_order_item_id' => $item->purchase_order_item_id,
                'item_id' => $item->item_id,
                'qty' => $item->qty,
                'price' => $item->purchaseOrderItem?->price ?? 0,
                'description' => $item->description,
                'sort' => $item->sort,
            ])
            ->values()
            ->all();
    }
}
