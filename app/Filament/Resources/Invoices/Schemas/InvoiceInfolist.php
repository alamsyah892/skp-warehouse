<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\PurchaseOrderTaxType;
use App\Filament\Components\Infolists\ActivityLogTab;
use App\Filament\Resources\GoodsReceives\GoodsReceiveResource;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Livewire\InvoiceItemsTable;
use App\Models\Invoice;
use App\Services\OrderCalculationService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Zvizvi\UserFields\Components\UserEntry;

class InvoiceInfolist
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
                    Grid::make() // left / 1
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ])
                        ->columns(1)
                        ->schema([
                            static::dataSection(), // 1.1

                            static::tabSection(), // 1.2

                            static::summaryTotalSection(), // 1.3
                        ]),

                    Grid::make() // right / 2
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 1,
                        ])
                        ->columns(1)
                        ->schema([
                            static::infoSection(), // 2.1

                            static::goodsReceiveInfoSection(), // 2.2

                            static::purchaseOrderInfoSection(), // 2.3

                            static::vendorInfoSection(), // 2.4
                        ]),
                ]),
        ]);
    }

    protected static function dataSection(): Section
    {
        return Section::make('Informasi Invoice')
            ->icon(Heroicon::DocumentText)
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
                                'default' => 3,
                                'lg' => 3,
                            ]),
                    ]),

                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->columns([
                        'default' => 2,
                        'lg' => 2,
                    ])
                    ->schema([
                        TextEntry::make('invoice_date')
                            ->label('Tgl Invoice')
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary')
                            ->date(),

                        TextEntry::make('invoice_due_date')
                            ->label('Jatuh Tempo')
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('warning')
                            ->date()
                            ->alignEnd(),
                    ]),

                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 7,
                    ])
                    ->columns([
                        'default' => 2,
                    ])
                    ->schema([
                        TextEntry::make('warehouse_name')
                            ->hiddenLabel()
                            ->icon(Heroicon::HomeModern)
                            ->iconColor('primary')
                            ->state(fn (Invoice $record): ?string => $record->goodsReceive?->warehouse?->name ?? $record->purchaseOrder?->warehouse?->name)
                            ->placeholder('-'),

                        TextEntry::make('company_alias')
                            ->hiddenLabel()
                            ->icon(Heroicon::BuildingOffice2)
                            ->iconColor('primary')
                            ->state(fn (Invoice $record): ?string => $record->goodsReceive?->company?->alias ?? $record->purchaseOrder?->company?->alias)
                            ->placeholder('-'),

                        TextEntry::make('division_name')
                            ->hiddenLabel()
                            ->icon(Heroicon::Briefcase)
                            ->iconColor('primary')
                            ->state(fn (Invoice $record): ?string => $record->goodsReceive?->division?->name ?? $record->purchaseOrder?->division?->name)
                            ->placeholder('-'),

                        TextEntry::make('project_name')
                            ->hiddenLabel()
                            ->icon(Heroicon::Square3Stack3d)
                            ->iconColor('primary')
                            ->state(fn (Invoice $record): ?string => $record->goodsReceive?->project?->name ?? $record->purchaseOrder?->project?->name)
                            ->placeholder('-'),
                    ]),

                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->columns([
                        'default' => 2,
                    ])
                    ->schema([
                        TextEntry::make('goodsReceive.number')
                            ->label(__('goods-receive.model.label'))
                            ->icon(Heroicon::InboxArrowDown)
                            ->iconColor('primary')
                            ->fontFamily(FontFamily::Mono)
                            ->badge()
                            ->url(
                                fn (Invoice $record): ?string => $record->goods_receive_id
                                    ? GoodsReceiveResource::getUrl('view', ['record' => $record->goods_receive_id])
                                    : null
                            )
                            ->columnSpan(fn (Invoice $record): int => $record->purchase_order_id ? 1 : 2),

                        TextEntry::make('purchaseOrder.number')
                            ->label(__('purchase-order.model.label'))
                            ->icon(Heroicon::ShoppingCart)
                            ->iconColor('primary')
                            ->fontFamily(FontFamily::Mono)
                            ->badge()
                            ->url(
                                fn (Invoice $record): ?string => $record->purchase_order_id
                                    ? PurchaseOrderResource::getUrl('view', ['record' => $record->purchase_order_id])
                                    : null
                            )
                            ->visible(fn (Invoice $record): bool => filled($record->purchase_order_id)),

                        TextEntry::make('vendor.name')
                            ->hiddenLabel()
                            ->icon(Heroicon::BuildingStorefront)
                            ->iconColor('primary')
                            ->columnSpanFull(),
                    ]),

                TextEntry::make('description')
                    ->label(__('common.description.label'))
                    ->columnSpanFull()
                    ->color('gray')
                    ->placeholder('-')
                    ->formatStateUsing(fn ($state) => nl2br(e($state)))
                    ->html()
                    ->visible(fn ($state): bool => filled($state)),
            ]);
    }

    protected static function tabSection(): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                Tab::make('Item Invoice')
                    ->icon(Heroicon::Cube)
                    ->badge(fn (Invoice $record): ?int => $record->invoiceItems?->count() ?: null)
                    ->schema([
                        Livewire::make(InvoiceItemsTable::class),
                    ]),

                ActivityLogTab::make(__('common.log_activity.label')),
            ]);
    }

    protected static function summaryTotalSection(): Section
    {
        return Section::make(__('purchase-order.section.summary_total.label'))
            ->icon(Heroicon::Calculator)
            ->iconColor('primary')
            ->columnSpanFull()
            ->columns([
                'default' => 1,
                'lg' => 12,
            ])
            ->compact()
            ->schema([
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->schema([
                        TextEntry::make('tax_type')
                            ->label(__('purchase-order.tax_type.label'))
                            ->formatStateUsing(fn ($state) => $state instanceof PurchaseOrderTaxType ? $state->label() : (PurchaseOrderTaxType::tryFrom((string) $state)?->label() ?? '-'))
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpanFull(),
                        TextEntry::make('tax_percentage')
                            ->label(__('purchase-order.tax_percentage.label'))
                            ->formatStateUsing(fn ($state) => filled($state) ? ($state + 0).'%' : '-')
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpanFull(),
                        TextEntry::make('tax_description')
                            ->label(__('purchase-order.tax_description.label'))
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpanFull(),
                    ]),
                Fieldset::make(__('purchase-order.fieldset.detail_total.label'))
                    ->columns([
                        'default' => 1,
                        'lg' => 1,
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 7,
                    ])
                    ->inlineLabel()
                    ->schema([
                        TextEntry::make('total_subtotal')
                            ->label(__('purchase-order.subtotal.label'))
                            ->state(fn ($record) => static::getSummary($record)['subtotal'] ?? 0)
                            ->numeric()
                            ->alignEnd(),
                        TextEntry::make('total_discount')
                            ->label(__('purchase-order.discount.label'))
                            ->state(fn ($record) => '-'.static::getSummary($record)['discount'] ?? 0)
                            ->numeric()
                            ->color('danger')
                            ->alignEnd(),
                        TextEntry::make('total_after_discount')
                            ->label(__('purchase-order.after_discount.label'))
                            ->state(fn ($record) => static::getSummary($record)['subtotal_after_discount'] ?? 0)
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->alignEnd(),

                        View::make('components.divider'),

                        TextEntry::make('total_dpp')
                            ->label(__('purchase-order.tax_base.label'))
                            ->state(fn ($record) => static::getSummary($record)['tax_base'] ?? static::getSummary($record)['dpp'] ?? 0)
                            ->numeric()
                            ->alignEnd(),
                        TextEntry::make('total_ppn')
                            ->label(fn ($get) => __('purchase-order.tax.label', ['percentage' => $get('tax_percentage') > 0 ? "({$get('tax_percentage')}%)" : '']))
                            ->state(fn ($record) => static::getSummary($record)['tax_amount'] ?? 0)
                            ->numeric()
                            ->color('warning')
                            ->alignEnd(),
                        TextEntry::make('total_before_rounding')
                            ->label(__('purchase-order.total.label'))
                            ->state(fn ($record) => static::getSummary($record)['total_before_rounding'] ?? 0)
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->alignEnd(),
                        TextEntry::make('summary_rounding')
                            ->label(__('purchase-order.rounding.label'))
                            ->state(fn ($record) => $record->rounding ?? 0)
                            ->numeric()
                            ->alignEnd(),
                        TextEntry::make('total_grand_total')
                            ->label(__('purchase-order.grand_total.label'))
                            ->state(fn ($record) => static::getSummary($record)['grand_total'] ?? 0)
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
        return Section::make(__('purchase-order.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->schema([
                TextEntry::make('notes')
                    ->label(__('purchase-request.notes.label'))
                    ->formatStateUsing(fn ($state) => nl2br(e($state)))
                    ->html()
                    ->placeholder('-')
                    ->color('gray'),

                UserEntry::make('user')
                    ->label(__('common.log_activity.created.label').' '.__('common.log_activity.by'))
                    ->color('gray'),

                TextEntry::make('created_at')
                    ->label(__('common.created_at.label'))
                    ->dateTime()
                    ->size(TextSize::Small)
                    ->color('gray'),

                TextEntry::make('updated_at')
                    ->label(__('common.updated_at.label'))
                    ->dateTime()
                    ->size(TextSize::Small)
                    ->color('gray'),

                TextEntry::make('deleted_at')
                    ->label(__('common.deleted_at.label'))
                    ->dateTime()
                    ->size(TextSize::Small)
                    ->color('danger')
                    ->visible(fn ($state): bool => $state !== null),

                TextEntry::make('info')
                    ->label('Info')
                    ->formatStateUsing(fn ($state) => collect(explode("\n", (string) $state))->map(fn ($line) => '• '.$line)->implode('<br>'))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->visible(fn ($state): bool => filled($state)),
            ]);
    }

    protected static function goodsReceiveInfoSection(): Section
    {
        return Section::make(__('goods-receive.section.main_info.label'))
            ->icon(Heroicon::InboxArrowDown)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->visible(fn (?Invoice $record): bool => $record?->goods_receive_id !== null)
            ->schema([
                TextEntry::make('goodsReceive.number')
                    ->hiddenLabel()
                    ->icon(Heroicon::Hashtag)
                    ->iconColor('primary')
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->badge()
                    ->url(
                        fn (Invoice $record): ?string => $record->goods_receive_id
                            ? GoodsReceiveResource::getUrl('view', ['record' => $record->goods_receive_id])
                            : null
                    ),

                Grid::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('goodsReceive.delivery_order')
                            ->hiddenLabel()
                            ->icon(Heroicon::Truck)
                            ->iconColor('primary')
                            ->placeholder('-'),

                        TextEntry::make('goodsReceive.created_at')
                            ->hiddenLabel()
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary')
                            ->date()
                            ->alignEnd(),
                    ]),

                TextEntry::make('goodsReceive.description')
                    ->hiddenLabel()
                    ->formatStateUsing(fn ($state) => nl2br(e($state)))
                    ->html()
                    ->color('gray')
                    ->visible(fn ($state): bool => filled($state)),

                UserEntry::make('goodsReceive.user')
                    ->hiddenLabel()
                    ->color('gray'),
            ]);
    }

    protected static function purchaseOrderInfoSection(): Section
    {
        return Section::make(__('purchase-order.section.main_info.label'))
            ->icon(Heroicon::ShoppingCart)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->visible(fn (?Invoice $record): bool => $record?->purchase_order_id !== null)
            ->schema([
                TextEntry::make('purchaseOrder.number')
                    ->hiddenLabel()
                    ->icon(Heroicon::Hashtag)
                    ->iconColor('primary')
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->badge()
                    ->url(
                        fn (Invoice $record): ?string => $record->purchase_order_id
                            ? PurchaseOrderResource::getUrl('view', ['record' => $record->purchase_order_id])
                            : null
                    ),

                Grid::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('purchaseOrder.status')
                            ->hiddenLabel()
                            ->icon(fn ($state) => $state?->icon())
                            ->formatStateUsing(fn ($state) => $state?->label())
                            ->color(fn ($state) => $state?->color())
                            ->badge(),

                        TextEntry::make('purchaseOrder.created_at')
                            ->hiddenLabel()
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary')
                            ->date()
                            ->alignEnd(),
                    ]),

                TextEntry::make('purchaseOrder.description')
                    ->hiddenLabel()
                    ->formatStateUsing(fn ($state) => nl2br(e($state)))
                    ->html()
                    ->color('gray')
                    ->visible(fn ($state): bool => filled($state)),

                UserEntry::make('purchaseOrder.user')
                    ->hiddenLabel()
                    ->color('gray'),
            ]);
    }

    protected static function vendorInfoSection(): Section
    {
        return Section::make(__('vendor.section.main_info.label'))
            ->icon(Heroicon::BuildingStorefront)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->columnSpanFull()
            ->visible(fn (?Invoice $record): bool => $record?->vendor !== null)
            ->schema([
                TextEntry::make('vendor.name')
                    ->hiddenLabel()
                    ->icon(Heroicon::BuildingStorefront)
                    ->iconColor('primary')
                    ->weight(FontWeight::Bold),

                TextEntry::make('vendor.address')
                    ->hiddenLabel()
                    ->icon(Heroicon::MapPin)
                    ->iconColor('primary')
                    ->state(fn (Invoice $record): ?string => collect([$record->vendor?->address, $record->vendor?->city])->filter()->join(' - '))
                    ->color('gray'),

                Grid::make()
                    ->schema([
                        TextEntry::make('vendor.phone')
                            ->hiddenLabel()
                            ->icon(Heroicon::Phone)
                            ->iconColor('primary')
                            ->color('gray')
                            ->visible(fn ($state): bool => $state !== null),

                        TextEntry::make('vendor.fax')
                            ->hiddenLabel()
                            ->icon(Heroicon::DocumentText)
                            ->iconColor('primary')
                            ->color('gray')
                            ->visible(fn ($state): bool => $state !== null),
                    ]),

                TextEntry::make('vendor.contact_person')
                    ->hiddenLabel()
                    ->icon(Heroicon::UserCircle)
                    ->iconColor('primary')
                    ->color('gray')
                    ->visible(fn ($state): bool => $state !== null),

                TextEntry::make('vendor.email')
                    ->hiddenLabel()
                    ->icon(Heroicon::Envelope)
                    ->iconColor('primary')
                    ->color('gray')
                    ->visible(fn ($state): bool => $state !== null),

                TextEntry::make('vendor.website')
                    ->hiddenLabel()
                    ->icon(Heroicon::GlobeAlt)
                    ->iconColor('primary')
                    ->color('gray')
                    ->visible(fn ($state): bool => $state !== null),
            ]);
    }

    protected static function getSummary($record): array
    {
        static $summaries = [];

        if (! isset($summaries[$record->id])) {
            $summaries[$record->id] = OrderCalculationService::calculateSummary(
                $record->invoiceItems->map(fn ($item) => [
                    'id' => $item->id,
                    'qty' => $item->qty,
                    'price' => $item->price,
                ])->all(),
                $record->discount,
                $record->tax_type,
                $record->tax_percentage,
                $record->rounding
            );
        }

        return $summaries[$record->id];
    }
}
