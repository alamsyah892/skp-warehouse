<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderTaxType;
use App\Filament\Components\Infolists\ActivityLogTab;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Collection;
use Zvizvi\UserFields\Components\UserEntry;

class PurchaseOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->columnSpanFull()
                ->columns(['default' => 1, 'xl' => 4, '2xl' => 4])
                ->schema([
                    Grid::make()
                        ->columnSpan(['xl' => 3, '2xl' => 3])
                        ->schema([static::dataSection(), static::tabSection(), static::totalSection()]),
                    Grid::make()
                        ->columnSpan(['xl' => 1, '2xl' => 1])
                        ->schema([static::otherInfoSection(), static::relatedDataSection()]),
                ]),
        ]);
    }

    protected static function dataSection(): Section
    {
        return Section::make(__('purchase-order.section.main_info.label'))
            ->icon(Heroicon::ClipboardDocumentList)
            ->iconColor('primary')
            ->description(__('purchase-order.section.main_info.description'))
            ->footer(fn($record) => self::dataSectionFooter($record))
            ->collapsible()
            ->columnSpanFull()
            ->columns(3)
            ->compact()
            ->schema([
                Grid::make()
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('number')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->fontFamily(FontFamily::Mono)
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->icon(Heroicon::Hashtag)
                            ->iconColor('primary'),

                        TextEntry::make('vendor.name')->hiddenLabel()->icon(Heroicon::BuildingStorefront)->iconColor('primary')->columnSpanFull(),

                        TextEntry::make('warehouse.name')->hiddenLabel()->icon(Heroicon::HomeModern)->iconColor('primary'),
                        TextEntry::make('company.alias')->hiddenLabel()->icon(Heroicon::BuildingOffice2)->iconColor('primary'),
                        TextEntry::make('division.name')->hiddenLabel()->icon(Heroicon::Briefcase)->iconColor('primary'),
                        TextEntry::make('project.name')->hiddenLabel()->icon(Heroicon::Square3Stack3d)->iconColor('primary'),

                        TextEntry::make('warehouseAddress.address')
                            ->label(__('purchase-order.warehouse_address.label'))
                            ->columnSpanFull()
                            ->color('gray')
                            ->icon(Heroicon::MapPin)
                            ->iconColor('primary')
                            ->placeholder('-')
                            ->formatStateUsing(
                                fn($state, $record) => collect([$state, $record->warehouseAddress?->city])
                                    ->filter()
                                    ->join(' - ') ?: '-'
                            ),
                        TextEntry::make('description')
                            ->label(__('common.description.label'))
                            ->columnSpanFull()
                            ->color('gray')
                            ->placeholder('-')
                            ->formatStateUsing(fn($state) => nl2br(e($state)))
                            ->html(),
                        RepeatableEntry::make('purchaseRequests')
                            ->label(__('purchase-request.model.plural_label'))
                            ->schema([
                                TextEntry::make('number')
                                    ->hiddenLabel()
                                    ->fontFamily(FontFamily::Mono)
                                    ->badge()
                                    ->url(fn(PurchaseRequest $record): string => PurchaseRequestResource::getUrl('view', ['record' => $record]))
                                    ->openUrlInNewTab(),
                            ])
                            ->columnSpanFull()
                            ->contained(false),
                    ]),
                Grid::make()
                    ->columns(1)
                    ->schema([
                        TextEntry::make('created_at')->hiddenLabel()->date()->icon(Heroicon::CalendarDays)->iconColor('primary'),
                        UserEntry::make('user')->wrapped(),
                        TextEntry::make('status')
                            ->formatStateUsing(fn($state) => $state?->label())
                            ->icon(fn($state) => $state?->icon())
                            ->badge()
                            ->color(fn($state) => $state?->color()),
                        RepeatableEntry::make('statusLogs')
                            ->label('Status Timeline')
                            ->schema([
                                TextEntry::make('to_status')
                                    ->hiddenLabel()
                                    ->formatStateUsing(function ($state, $record) {
                                        $status = $state?->label();
                                        $user = $record->user?->name ?? 'System';
                                        $date = $record->created_at->format('M d, Y');

                                        return __('common.log_format_with_date', [
                                            'date' => $date,
                                            'status' => $status,
                                            'user' => $user,
                                        ]);
                                    })
                                    ->icon(fn($state) => $state?->icon())
                                    ->iconColor(fn($state) => $state?->color())
                                    ->color('gray'),
                                TextEntry::make('note')
                                    ->label('')
                                    ->visible(fn($state) => filled($state))
                                    ->formatStateUsing(fn($state) => "Note: {$state}")
                                    ->color('gray')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull()
                            ->contained(false),
                    ]),
            ]);
    }

    protected static function dataSectionFooter($record): array
    {
        return collect($record->getNextStatuses())->map(function ($status) use ($record) {
            return Action::make('changeStatus' . $status->value)
                ->label(__($status->actionLabel()))
                ->color($status->color())
                ->icon($status->icon())
                ->requiresConfirmation()
                ->modalHeading(__($status->actionLabel()) . ' ' . __('purchase-order.model.label'))
                ->modalDescription(__('purchase-order.status.action.note', ['status' => __($status->label())]))
                ->action(function () use ($status, $record) {
                    if ($status === PurchaseOrderStatus::ORDERED) {
                        $record->markAsOrdered();
                    } else {
                        $record->changeStatus($status);
                    }

                    Notification::make()
                        ->success()
                        ->title(__('purchase-order.status.action.changed'))
                        ->send();

                    return redirect(request()->header('Referer'));
                });
        })->values()->all();
    }

    protected static function tabSection(): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                Tab::make(__('purchase-order.section.purchase_order_items.label'))
                    ->icon(Heroicon::OutlinedCube)
                    ->badge(fn($record) => $record->purchaseOrderItems?->count() ?: null)
                    ->badgeTooltip(__('purchase-order.purchase_order_items.count_label'))
                    ->schema([
                        Callout::make()
                            ->description(__('purchase-order.section.purchase_order_items.description'))
                            ->info()
                            ->color(null),
                        RepeatableEntry::make('purchaseOrderItems')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('#')->wrapHeader(false),
                                TableColumn::make(__('item.related.code.label')),
                                TableColumn::make(__('item.related.name.label')),
                                TableColumn::make('Unit'),
                                TableColumn::make('Qty'),
                                TableColumn::make(__('purchase-order.purchase_order_item.price.label'))->wrapHeader(),
                                TableColumn::make('Subtotal')->wrapHeader(),
                            ])
                            ->schema([
                                TextEntry::make('sort')->label('#')->wrap(false),

                                TextEntry::make('item.code')
                                    ->label(__('item.related.code.label'))
                                    ->fontFamily(FontFamily::Mono)
                                    ->weight(FontWeight::Bold)
                                    ->icon(Heroicon::Hashtag)
                                // ->badge()
                                ,
                                TextEntry::make('item.name')
                                    ->label(__('item.related.name.label'))
                                    ->formatStateUsing(
                                        fn(PurchaseOrderItem $record): ViewContract =>
                                        static::purchaseOrderItemSummaryView($record)
                                    )
                                ,
                                TextEntry::make('item.unit')
                                    ->label(__('item.related.unit.label'))
                                ,
                                TextEntry::make('qty')
                                    ->numeric()
                                    ->alignment(Alignment::End)
                                ,

                                // TextEntry::make('sort')->label('#')->wrap(false),
                                // TextEntry::make('item.name')->label('Item')->wrap()
                                //     ->state(fn(PurchaseOrderItem $record) => collect([
                                //         collect([$record->item?->code, $record->item?->name])->filter()->implode(' | '),
                                //         filled($record->description) ? nl2br(e($record->description)) : null,
                                //     ])->filter()->implode('<br>'))
                                //     ->html()
                                // ,
                                // TextEntry::make('purchaseRequestItem.purchaseRequest.number')
                                //     ->label('PR')
                                //     ->placeholder('-')
                                //     ->url(fn(PurchaseOrderItem $record): ?string => $record->purchaseRequestItem?->purchaseRequest
                                //         ? PurchaseRequestResource::getUrl('view', ['record' => $record->purchaseRequestItem->purchaseRequest])
                                //         : null)
                                //     ->openUrlInNewTab()
                                //     ->fontFamily(FontFamily::Mono)
                                // ,
                                // TextEntry::make('item.unit')->label(__('item.related.unit.label')),
                                // TextEntry::make('qty')->numeric()->alignment(Alignment::End)->wrap(false),
                                TextEntry::make('price')
                                    ->label(__('purchase-order.purchase_order_item.price.label'))
                                    ->numeric()
                                    ->alignment(Alignment::End)
                                ,
                                TextEntry::make('total')
                                    ->label('Subtotal')
                                    ->state(fn(PurchaseOrderItem $record): float => static::getLineSummary($record)['subtotal'] ?? 0.0)
                                    ->numeric()
                                    ->alignment(Alignment::End)
                                    ->wrap(false)
                                ,
                            ])
                        ,
                    ]),
                ActivityLogTab::make(__('common.log_activity.label')),
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
                        TextEntry::make('tax_type')
                            ->label(__('purchase-order.tax_type.label'))
                            ->formatStateUsing(fn($state) => $state instanceof PurchaseOrderTaxType ? $state->label() : (PurchaseOrderTaxType::tryFrom((string) $state)?->label() ?? '-'))
                            ->placeholder('-')
                            ->columnSpanFull()
                        ,
                        TextEntry::make('tax_percentage')
                            ->label(__('purchase-order.tax_percentage.label'))
                            ->formatStateUsing(fn($state) => filled($state) ? ($state + 0) . '%' : '-')
                            ->placeholder('-')
                            ->columnSpanFull()
                        ,
                        TextEntry::make('tax_description')
                            ->placeholder('-')
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
                            ->state(fn($record) => static::formatMoney(static::getSummary($record)['subtotal'] ?? 0))
                            ->numeric()
                            ->inlineLabel()
                            ->alignEnd(),
                        TextEntry::make('total_discount')
                            ->label('Diskon')
                            ->state(fn($record) => '-' . static::formatMoney(static::getSummary($record)['discount'] ?? 0))
                            ->numeric()
                            ->color('danger')
                            ->inlineLabel()
                            ->alignEnd(),
                        TextEntry::make('total_after_discount')
                            ->label('Subtotal Setelah Diskon')
                            ->state(fn($record) => static::formatMoney(static::getSummary($record)['subtotal_after_discount'] ?? 0))
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->inlineLabel()
                            ->alignEnd(),

                        View::make('components.divider'),

                        TextEntry::make('total_dpp')
                            ->label('DPP')
                            ->state(fn($record) => static::formatMoney(static::getSummary($record)['dpp'] ?? 0))
                            ->numeric()
                            ->inlineLabel()
                            ->alignEnd(),
                        TextEntry::make('total_ppn')
                            ->label(fn($record) => filled($record->tax_percentage) ? "PPN ({$record->tax_percentage}%)" : 'PPN')
                            ->state(fn($record) => static::formatMoney(static::getSummary($record)['tax_amount'] ?? 0))
                            ->numeric()
                            ->color('warning')
                            ->inlineLabel()
                            ->alignEnd(),
                        TextEntry::make('total_before_rounding')
                            ->label('Total')
                            ->state(fn($record) => static::formatMoney(static::getSummary($record)['total_before_rounding'] ?? 0))
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->inlineLabel()
                            ->alignEnd(),
                        TextEntry::make('summary_rounding')
                            ->label('Pembulatan')
                            ->state(fn($record) => static::formatMoney($record->rounding ?? 0))
                            ->numeric()
                            ->inlineLabel()
                            ->alignEnd(),
                        TextEntry::make('total_grand_total')
                            ->label('Total Pembayaran')
                            ->state(fn($record) => static::formatMoney(static::getSummary($record)['grand_total'] ?? 0))
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->color('primary')
                            ->inlineLabel()
                            ->alignEnd(),
                    ])
                ,
            ]);
    }

    protected static function otherInfoSection(): Section
    {
        return Section::make(__('purchase-order.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->description(__('purchase-order.section.other_info.description'))
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                TextEntry::make('memo')->color('gray')->placeholder('-'),
                TextEntry::make('termin')->color('gray')->placeholder('-'),
                TextEntry::make('notes')
                    ->label(__('purchase-order.notes.label'))
                    ->columnSpanFull()
                    ->placeholder('-')
                    ->color('gray')
                    ->formatStateUsing(fn($state) => nl2br(e($state)))
                    ->html(),
                TextEntry::make('info')
                    ->label(__('purchase-order.revision_history.label'))
                    ->placeholder('-')
                    ->visible(fn($record) => !$record?->hasStatus(PurchaseOrderStatus::DRAFT))
                    ->columnSpanFull()
                    ->formatStateUsing(
                        fn($state) => collect(explode("\n", $state))
                            ->map(fn($line) => '• ' . e($line))
                            ->implode('<br>')
                    )
                    ->html()
                    ->color('gray'),
                TextEntry::make('created_at')->date()->label(__('common.created_at.label'))->color('gray')->size(TextSize::Small),
                TextEntry::make('updated_at')->date()->label(__('common.updated_at.label'))->color('gray')->size(TextSize::Small),
                TextEntry::make('deleted_at')->date()
                    ->label(__('common.deleted_at.label'))
                    ->color('gray')
                    ->size(TextSize::Small)
                    ->visible(fn($state) => $state != null),
            ]);
    }

    protected static function relatedDataSection(): Section|string
    {
        return Section::make(__('purchase-request.model.plural_label'))
            ->icon(Heroicon::DocumentText)
            ->iconColor('primary')
            ->collapsible()
            ->columnSpanFull()
            ->compact()
            ->schema(fn(PurchaseOrder $record): array => static::getPurchaseRequestHeaderEntries($record));
    }

    protected static function getBreakdown($record): array
    {
        static $breakdowns = [];
        if (!isset($breakdowns[$record->id])) {
            $breakdowns[$record->id] = PurchaseOrder::calculateOrderBreakdown(
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
        return $breakdowns[$record->id];
    }

    protected static function getLineSummary(PurchaseOrderItem $item): array
    {
        return static::getBreakdown($item->purchaseOrder)['lines'][$item->id] ?? [];
    }

    protected static function getSummary($record): array
    {
        return static::getBreakdown($record)['summary'] ?? [];
    }

    protected static function getPurchaseRequestHeaderEntries(PurchaseOrder $record): array
    {
        /** @var Collection<int, PurchaseRequest> $purchaseRequests */
        $purchaseRequests = $record->purchaseRequests;

        if ($purchaseRequests->isEmpty()) {
            return [
                TextEntry::make('purchase_request_detail_empty')
                    ->hiddenLabel()
                    ->state(__('Pilih pengajuan untuk melihat detail')),
            ];
        }

        return $purchaseRequests
            ->map(function (PurchaseRequest $purchaseRequest): Fieldset {
                return Fieldset::make($purchaseRequest->number)
                    ->schema([
                        TextEntry::make("purchase_request_{$purchaseRequest->id}_created_at")
                            ->hiddenLabel()
                            ->state($purchaseRequest->created_at)
                            ->date()
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary'),
                        TextEntry::make("purchase_request_{$purchaseRequest->id}_status")
                            ->hiddenLabel()
                            ->state($purchaseRequest->status->label())
                            ->icon($purchaseRequest->status->icon())
                            ->badge()
                            ->color($purchaseRequest->status->color())
                            ->alignEnd(),
                        TextEntry::make("purchase_request_{$purchaseRequest->id}_warehouse_address")
                            ->hiddenLabel()
                            ->color('gray')
                            ->icon(Heroicon::MapPin)
                            ->iconColor('primary')
                            ->placeholder('-')
                            ->state($purchaseRequest->warehouseAddress
                                ? collect([$purchaseRequest->warehouseAddress->address, $purchaseRequest->warehouseAddress->city])->filter()->join(' - ')
                                : '')
                            ->columnSpanFull(),
                        TextEntry::make("purchase_request_{$purchaseRequest->id}_description")
                            ->hiddenLabel()
                            ->state($purchaseRequest->description)
                            ->placeholder('-')
                            ->color('gray')
                            ->size(TextSize::Small)
                            ->formatStateUsing(fn($state) => nl2br(e($state)))
                            ->html()
                            ->columnSpanFull(),
                        UserEntry::make("purchase_request_{$purchaseRequest->id}_user")
                            ->hiddenLabel()
                            ->state($purchaseRequest->user)
                            ->wrapped(),
                    ]);
            })
            ->all();
    }

    protected static function formatMoney(float $amount): string
    {
        return
            // 'Rp ' .
            number_format($amount, 2, ',', '.');
    }

    public static function purchaseOrderItemSummaryView(PurchaseOrderItem $record): ViewContract
    {
        return view('filament.infolists.purchase-order-item-summary', [
            'itemName' => $record->item?->name,
            'description' => filled($record->description) ? $record->description : null,
            'purchaseRequestNumber' => $record->purchaseRequestItem?->purchaseRequest?->number,
        ]);
    }
}
