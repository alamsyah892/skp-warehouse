<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Enums\GoodsReceiveStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderTaxType;
use App\Filament\Components\Infolists\ActivityLogTab;
use App\Filament\Components\Infolists\StatusTimelineSection;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Livewire\PurchaseOrderGoodsReceivesTable;
use App\Livewire\PurchaseOrderItemsTable;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
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

class PurchaseOrderInfolist
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

                            static::tabSection(), // 1.2

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

                            StatusTimelineSection::make(), // 2.4
                        ])
                    ,
                ])
            ,
        ]);
    }

    protected static function dataSection(): Section
    {
        return Section::make(__('purchase-order.section.main_info.label'))
            ->icon(Heroicon::ShoppingCart)
            ->iconColor('primary')
            ->compact()
            ->footer(fn($record) => self::dataSectionFooter($record))
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
                        ,
                        TextEntry::make('type')
                            ->hiddenLabel()
                            ->icon(fn($state) => $state?->icon())
                            ->formatStateUsing(fn($state) => $state?->label())
                            ->size(TextSize::Large)
                            ->color(fn($state) => $state?->color())
                            ->badge()
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
                        ,
                        TextEntry::make('created_at')
                            ->hiddenLabel()
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary')
                            ->date()
                            ->alignEnd()
                        ,
                    ])
                ,
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 7,
                    ])
                    ->columns([
                        'default' => 2
                    ])
                    ->schema([
                        TextEntry::make('vendor.name')->hiddenLabel()->icon(Heroicon::BuildingStorefront)->iconColor('primary')->columnSpanFull(),

                        TextEntry::make('warehouse.name')
                            ->hiddenLabel()
                            ->icon(Heroicon::HomeModern)
                            ->iconColor('primary')
                        ,
                        TextEntry::make('company.alias')
                            ->hiddenLabel()
                            ->icon(Heroicon::BuildingOffice2)
                            ->iconColor('primary')
                        ,
                        TextEntry::make('division.name')
                            ->hiddenLabel()
                            ->icon(Heroicon::Briefcase)
                            ->iconColor('primary')
                        ,
                        TextEntry::make('project.name')
                            ->hiddenLabel()
                            ->icon(Heroicon::Square3Stack3d)
                            ->iconColor('primary')
                        ,
                        RepeatableEntry::make('purchaseRequests')
                            ->label(__('purchase-request.model.plural_label'))
                            ->schema([
                                TextEntry::make('number')
                                    ->hiddenLabel()
                                    ->icon(Heroicon::Hashtag)
                                    ->iconColor('primary')
                                    ->fontFamily(FontFamily::Mono)
                                    ->badge()
                                    ->url(fn(PurchaseRequest $record): string => PurchaseRequestResource::getUrl('view', ['record' => $record]))
                                    ->openUrlInNewTab()
                                ,
                            ])
                            ->columnSpanFull()
                            ->contained(false)
                        ,
                        TextEntry::make('description')
                            ->label(__('common.description.label'))
                            ->columnSpanFull()
                            ->color('gray')
                            ->placeholder('-')
                            ->formatStateUsing(fn($state) => nl2br($state))
                            ->html()
                        ,
                    ])
                ,
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->columns([
                        'default' => 2,
                    ])
                    ->schema([
                        TextEntry::make('warehouseAddress.address')
                            ->label(__('purchase-request.warehouse_address.label'))
                            ->icon(Heroicon::MapPin)
                            ->iconColor('primary')
                            ->formatStateUsing(
                                fn($state, $record) =>
                                // $state . "</br>" . $record->warehouseAddress?->city
                                collect([$state, $record->warehouseAddress?->city])
                                    ->filter()
                                    ->join(' - ') ?: '-'
                            )
                            ->html()
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpanFull()
                        ,
                        TextEntry::make('delivery_date')
                            ->label(__('purchase-order.delivery_date.label'))
                            ->date()
                            ->color('gray')
                            ->placeholder('-')
                        ,
                        TextEntry::make('shipping_method')
                            ->label(__('purchase-order.shipping_method.label'))
                            ->placeholder('-')
                            ->color('gray')
                        ,
                        TextEntry::make('delivery_notes')
                            ->label(__('purchase-order.delivery_notes.label'))
                            ->formatStateUsing(fn($state) => nl2br($state))
                            ->html()
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpanFull()
                        ,
                        TextEntry::make('terms')
                            ->formatStateUsing(fn($state) => nl2br($state))
                            ->html()
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpanFull()
                        ,
                    ])
                ,
            ])
        ;
    }

    protected static function dataSectionFooter($record): array
    {
        return collect($record->getNextStatuses())
            ->reject(fn(PurchaseOrderStatus $status): bool => static::shouldHideStatusAction($record, $status))
            ->map(function ($status) use ($record) {
                return Action::make('changeStatus' . $status->value)
                    ->label(__($status->actionLabel()))
                    ->color($status->color())
                    ->icon($status->icon())
                    ->requiresConfirmation()
                    ->modalHeading(__($status->actionLabel()) . ' ' . __('purchase-order.model.label'))
                    ->modalDescription(__('purchase-order.status.action.note', ['status' => __($status->label())]))
                    ->action(function () use ($status, $record) {
                        $record->changeStatus($status);

                        Notification::make()
                            ->success()
                            ->title(__('purchase-order.status.action.changed'))
                            ->send();

                        return redirect(request()->header('Referer'));
                    })
                ;
            })
            ->values()
            ->all();
    }

    protected static function shouldHideStatusAction(PurchaseOrder $record, PurchaseOrderStatus $status): bool
    {
        if ($record->goodsReceives()->exists()) {
            if ($status === PurchaseOrderStatus::CANCELED) {
                $hasNotCanceledGR = $record->goodsReceives()->whereNot('status', GoodsReceiveStatus::CANCELED)->exists();

                return $hasNotCanceledGR;
            }

            if ($status === PurchaseOrderStatus::FINISHED) {
                $hasNotConfirmedGR = $record->goodsReceives()->whereNot('status', GoodsReceiveStatus::CONFIRMED)->exists();

                $hasRemainingQty = $record->purchaseRequestItems->contains(
                    fn($item): bool => $item->getRemainingQty() > 0
                );

                return $hasNotConfirmedGR || $hasRemainingQty;
            }
        }

        return false;
    }

    protected static function tabSection(): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                Tab::make(__('purchase-order.section.purchase_order_items.label'))
                    ->icon(Heroicon::Cube)
                    ->badge(fn($record) => $record->purchaseOrderItems?->count() ?: null)
                    ->badgeTooltip(__('purchase-order.purchase_order_items.count_label'))
                    ->schema([
                        // Callout::make()
                        //     ->description(__('purchase-order.section.purchase_order_items.description'))
                        //     ->info()
                        //     ->color(null),

                        Livewire::make(PurchaseOrderItemsTable::class),
                    ])
                ,

                Tab::make(__('goods-receive.model.plural_label'))
                    ->icon(Heroicon::InboxArrowDown)
                    ->badge(fn($record) => $record->goodsReceives?->count() ?: null)
                    ->schema([
                        Livewire::make(PurchaseOrderGoodsReceivesTable::class),
                    ])
                    ->visible(fn($record) => $record && $record->goodsReceives()->exists())
                ,

                ActivityLogTab::make(__('common.log_activity.label')),
            ])
        ;
    }

    protected static function summaryTotalSection(): Section
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
                        TextEntry::make('tax_type')
                            ->label(__('purchase-order.tax_type.label'))
                            ->formatStateUsing(fn($state) => $state instanceof PurchaseOrderTaxType ? $state->label() : (PurchaseOrderTaxType::tryFrom((string) $state)?->label() ?? '-'))
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpanFull()
                        ,
                        TextEntry::make('tax_percentage')
                            ->label(__('purchase-order.tax_percentage.label'))
                            ->formatStateUsing(fn($state) => filled($state) ? ($state + 0) . '%' : '-')
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpanFull()
                        ,
                        TextEntry::make('tax_description')
                            ->label(__('purchase-order.tax_description.label'))
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpanFull()
                        ,
                    ])
                ,
                Fieldset::make(__('purchase-order.fieldset.detail_total.label'))
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
                            ->state(fn($record) => static::getSummary($record)['subtotal'] ?? 0)
                            ->numeric()
                            ->alignEnd()
                        ,
                        TextEntry::make('total_discount')
                            ->label(__('purchase-order.discount.label'))
                            ->state(fn($record) => '-' . static::getSummary($record)['discount'] ?? 0)
                            ->numeric()
                            ->color('danger')
                            ->alignEnd()
                        ,
                        TextEntry::make('total_after_discount')
                            ->label(__('purchase-order.after_discount.label'))
                            ->state(fn($record) => static::getSummary($record)['subtotal_after_discount'] ?? 0)
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->alignEnd()
                        ,

                        View::make('components.divider'),

                        TextEntry::make('total_dpp')
                            ->label(__('purchase-order.tax_base.label'))
                            ->state(fn($record) => static::getSummary($record)['dpp'] ?? 0)
                            ->numeric()
                            ->alignEnd()
                        ,
                        TextEntry::make('total_ppn')
                            ->label(fn($get) => __('purchase-order.tax.label', ['percentage' => $get('tax_percentage') > 0 ? "({$get('tax_percentage')}%)" : '']))
                            ->state(fn($record) => static::getSummary($record)['tax_amount'] ?? 0)
                            ->numeric()
                            ->color('warning')
                            ->alignEnd()
                        ,
                        TextEntry::make('total_before_rounding')
                            ->label(__('purchase-order.total.label'))
                            ->state(fn($record) => static::getSummary($record)['total_before_rounding'] ?? 0)
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->alignEnd()
                        ,
                        TextEntry::make('summary_rounding')
                            ->label(__('purchase-order.rounding.label'))
                            ->state(fn($record) => $record->rounding ?? 0)
                            ->numeric()
                            ->alignEnd()
                        ,
                        TextEntry::make('total_grand_total')
                            ->label(__('purchase-order.grand_total.label'))
                            ->state(fn($record) => static::getSummary($record)['grand_total'] ?? 0)
                            ->numeric()
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

    protected static function getSummary($record): array
    {
        static $summaries = [];

        if (!isset($summaries[$record->id])) {
            $summaries[$record->id] = PurchaseOrder::calculateOrderSummary(
                $record->purchaseOrderItems->map(fn($item) => [
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
                    ->formatStateUsing(fn($state) => nl2br($state))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                ,
                UserEntry::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->color('gray')
                ,
                TextEntry::make('updated_at')->date()
                    ->label(__('common.updated_at.label'))
                    ->size(TextSize::Small)
                    ->color('gray')
                ,
                TextEntry::make('deleted_at')->date()
                    ->label(__('common.deleted_at.label'))
                    ->size(TextSize::Small)
                    ->color('gray')
                    ->visible(fn($state) => $state != null)
                ,
                TextEntry::make('info')
                    ->label(__('purchase-order.revision_history.label'))
                    ->formatStateUsing(fn($state) => collect(explode("\n", $state))->map(fn($line) => "• " . $line)->implode('<br>'))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->visible(fn($state, $record) => filled($state) && !$record?->hasStatus(PurchaseOrderStatus::DRAFT))
                ,
            ])
        ;
    }

    protected static function vendorInfoSection(): Section
    {
        return Section::make(__('vendor.section.main_info.label'))
            ->icon(Heroicon::BuildingStorefront)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->columnSpanFull()
            ->schema([
                TextEntry::make('vendor.name')
                    ->hiddenLabel()
                    ->icon(Heroicon::BuildingStorefront)
                    ->iconColor('primary')
                    ->weight(FontWeight::Bold)
                ,
                TextEntry::make('vendor.address')
                    ->hiddenLabel()
                    ->icon(Heroicon::MapPin)
                    ->iconColor('primary')
                    ->state(fn($record) => collect([$record->vendor?->address, $record->vendor?->city])->filter()->join(' - '))
                    ->color('gray')
                ,
                Grid::make()
                    ->schema([
                        TextEntry::make('vendor.phone')
                            ->hiddenLabel()
                            ->icon(Heroicon::Phone)
                            ->iconColor('primary')
                            ->color('gray')
                            ->visible(fn($state) => $state != null)
                        ,
                        TextEntry::make('vendor.fax')
                            ->hiddenLabel()
                            ->icon(Heroicon::DocumentText)
                            ->iconColor('primary')
                            ->color('gray')
                            ->visible(fn($state) => $state != null)
                        ,
                    ])
                ,
                TextEntry::make('vendor.contact_person')
                    ->hiddenLabel()
                    ->icon(Heroicon::UserCircle)
                    ->iconColor('primary')
                    ->color('gray')
                    ->visible(fn($state) => $state != null)
                ,
                TextEntry::make('vendor.email')
                    ->hiddenLabel()
                    ->icon(Heroicon::Envelope)
                    ->iconColor('primary')
                    ->color('gray')
                    ->visible(fn($state) => $state != null)
                ,
                TextEntry::make('vendor.website')
                    ->hiddenLabel()
                    ->icon(Heroicon::GlobeAlt)
                    ->iconColor('primary')
                    ->color('gray')
                    ->visible(fn($state) => $state != null)
                ,
            ])
        ;
    }

    protected static function purchaseRequestInfoSection(): Section|string
    {
        return Section::make(__('purchase-request.section.main_info.label'))
            ->icon(Heroicon::ClipboardDocumentList)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->columnSpanFull()
            ->schema(function (PurchaseOrder $record): array {
                $purchaseRequests = $record->purchaseRequests;

                if ($purchaseRequests->isEmpty()) {
                    return [];
                }

                return $purchaseRequests
                    ->map(function (PurchaseRequest $purchaseRequest): Section {
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
                                    ->url(fn(): string => PurchaseRequestResource::getUrl('view', ['record' => $purchaseRequest->id]))
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
                                    ->alignEnd()
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
                                    ->visible(fn($state) => filled($state))
                                ,
                                TextEntry::make("purchase_request_{$purchaseRequest->id}_description")
                                    ->hiddenLabel()
                                    ->columnSpanFull()
                                    ->color('gray')
                                    ->placeholder('-')
                                    ->state(nl2br($purchaseRequest->description))
                                    ->html()
                                    ->visible(fn($state) => filled($state))
                                ,
                                UserEntry::make("purchase_request_{$purchaseRequest->id}_user")
                                    ->hiddenLabel()
                                    ->state($purchaseRequest->user)
                                    ->color('gray')
                                    ->columnSpanFull()
                                ,
                            ]);
                    })
                    ->all()
                ;
            })
        ;
    }
}
