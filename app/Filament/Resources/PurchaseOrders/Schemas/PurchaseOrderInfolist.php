<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderTaxType;
use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
// use Kirschbaum\Commentions\Filament\Infolists\Components\CommentsEntry;
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
                        TextEntry::make('vendor.name')->hiddenLabel()->icon(Heroicon::Truck)->iconColor('primary'),
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
                        RepeatableEntry::make('purchaseRequests')
                            ->label(__('purchase-request.model.plural_label'))
                            ->schema([
                                TextEntry::make('number')
                                    ->hiddenLabel()
                                    ->fontFamily(FontFamily::Mono)
                                    ->badge(),
                            ])
                            ->columnSpanFull()
                            ->contained(false),
                        TextEntry::make('description')
                            ->label(__('common.description.label'))
                            ->columnSpanFull()
                            ->color('gray')
                            ->placeholder('-'),
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
                    $record->changeStatus($status);

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
                                // TableColumn::make(__('purchase-request.model.plural_label')),
                                // TableColumn::make(__('item.related.code.label'))->wrapHeader(),
                                TableColumn::make(__('item.related.name.label')),
                                TableColumn::make(__('item.related.unit.label'))->wrapHeader(),
                                TableColumn::make(__('purchase-order.purchase_order_item.qty.label'))->wrapHeader(),
                                TableColumn::make(__('purchase-order.purchase_order_item.price.label'))->wrapHeader(),
                                // TableColumn::make('Subtotal'),
                                TableColumn::make('Diskon'),
                                // TableColumn::make('Setelah Diskon')->wrapHeader(),
                                // TableColumn::make('DPP'),
                                // TableColumn::make('PPN'),
                                TableColumn::make('Total')->wrapHeader(),
                                // TableColumn::make(__('common.description.label')),
                            ])
                            ->schema([
                                TextEntry::make('sort')->label('#')->wrap(false),
                                // TextEntry::make('purchaseRequestItem.purchaseRequest.number')
                                //     ->label(__('purchase-request.model.plural_label'))
                                //     ->fontFamily(FontFamily::Mono)
                                //     ->badge(),
                                // TextEntry::make('item.code')
                                //     ->label(__('item.related.code.label'))
                                //     ->fontFamily(FontFamily::Mono)
                                //     ->weight(FontWeight::Bold)
                                //     ->icon(Heroicon::Hashtag)
                                //     ->badge(),
                                TextEntry::make('item.name')->label(__('item.related.name.label'))->wrap()
                                    ->state(fn($record) =>
                                        e($record->item->code) . ' | ' . e($record->item->name) . ' <br>' .
                                        e($record->purchaseRequestItem->purchaseRequest->number) . '<br>' .
                                        nl2br(e($record->description)))
                                    ->html()
                                ,
                                TextEntry::make('item.unit')->label(__('item.related.unit.label')),
                                TextEntry::make('qty')->numeric()->alignment(Alignment::End)->wrap(false),

                                TextEntry::make('price')
                                    ->label(__('purchase-order.purchase_order_item.price.label'))
                                    ->numeric()
                                    // ->state(function ($record) {
                                    //     $price = static::formatMoney($record->price);
                                    //     $subtotal = static::formatMoney($record->qty * $record->price);

                                    //     return "<div>{$price}</div><div class='text-xs text-gray-400 italic'>Sub: {$subtotal}</div>";
                                    // })
                                    // ->html()
                                    ->alignment(Alignment::End)
                                ,

                                // TextEntry::make('subtotal')
                                //     ->state(fn($record) => ($record->qty * $record->price))
                                //     ->numeric()
                                //     ->alignment(Alignment::End)
                                //     ->wrap(false)
                                // ,
                                TextEntry::make('discount')
                                    ->label(__('purchase-order.purchase_order_item.discount.label'))
                                    ->numeric()
                                    ->alignment(Alignment::End)
                                    ->wrap(false)
                                ,
                                // TextEntry::make('after_discount')
                                //     ->state(fn($record) => ($record->qty * $record->price) - $record->discount)
                                //     ->numeric()
                                //     ->alignment(Alignment::End)
                                //     ->wrap(false)
                                // ,
                                // TextEntry::make('dpp')
                                //     ->label(fn($component) => static::isInclude12($component->getContainer()->getParentComponent()->getRecord()) ? 'DPP (11/12)' : 'DPP')
                                //     ->state(fn($record, $component) => static::getLineBreakdown($record, $component)['tax_base'] ?? 0)
                                //     ->numeric()
                                //     ->alignment(Alignment::End)
                                //     ->wrap(false)
                                // ,
                                // TextEntry::make('tax')
                                //     ->label(function ($component) {
                                //         $po = $component->getContainer()->getParentComponent()->getRecord();
                                //         return filled($po->tax_percentage) ? "PPN ({$po->tax_percentage}%)" : 'PPN';
                                //     })
                                //     ->state(fn($record, $component) => static::getLineBreakdown($record, $component)['tax_amount'] ?? 0)
                                //     ->numeric()
                                //     ->color('warning')
                                //     ->alignment(Alignment::End)
                                //     ->wrap(false)
                                // ,
                                TextEntry::make('total')
                                    ->label(__('purchase-order.purchase_order_item.total.label'))
                                    ->state(fn($record) => $record->getLineTotalAmount())
                                    ->numeric()
                                    ->alignment(Alignment::End)
                                    ->wrap(false)
                                ,
                                // TextEntry::make('description')
                                //     ->label(__('common.description.label'))
                                //     ->color('gray')
                                //     ->placeholder('-')
                                //     ->wrap(false)
                                // ,
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
            ->columns(2)
            ->compact()
            ->schema([
                Grid::make()
                    ->columns(1)
                    ->schema([
                        // TextEntry::make('rounding')
                        //     ->label('Pembulatan')
                        //     ->numeric()
                        //     ->placeholder('0,00')
                        //     ->inlineLabel(),

                        TextEntry::make('tax_type')
                            ->label(__('purchase-order.tax_type.label'))
                            ->formatStateUsing(fn($state) => $state instanceof PurchaseOrderTaxType ? $state->label() : (PurchaseOrderTaxType::tryFrom((string) $state)?->label() ?? '-'))
                            ->placeholder('-'),
                        TextEntry::make('tax_percentage')
                            ->label(__('purchase-order.tax_percentage.label'))
                            ->formatStateUsing(fn($state) => filled($state) ? ($state + 0) . '%' : '-')
                            ->placeholder('-'),
                        TextEntry::make('tax_description')
                            // ->label(__('purchase-order.total.tax_description'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Rincian Total')
                    ->schema([
                        TextEntry::make('total_subtotal')
                            ->label('Subtotal')
                            ->state(fn($record) => static::formatMoney(static::getSummary($record)['gross_subtotal'] ?? 0))
                            ->numeric()
                            ->inlineLabel()
                            ->alignEnd(),
                        TextEntry::make('total_discount')
                            ->label('Diskon')
                            ->state(fn($record) => '-' . static::formatMoney(static::getSummary($record)['discount_total'] ?? 0))
                            ->numeric()
                            ->color('danger')
                            ->inlineLabel()
                            ->alignEnd(),
                        TextEntry::make('total_after_discount')
                            ->label('Subtotal Setelah Diskon')
                            ->state(fn($record) => static::formatMoney(static::getSummary($record)['gross_after_discount'] ?? 0))
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->inlineLabel()
                            ->alignEnd(),
                        TextEntry::make('total_dpp')
                            ->label(fn($record) => static::isInclude12($record) ? 'DPP (11/12)' : 'DPP')
                            ->state(fn($record) => static::formatMoney(static::getSummary($record)['tax_base'] ?? 0))
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
                            ->state(fn($record) => static::formatMoney(static::getSummary($record)['before_rounding'] ?? 0))
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
                    ->compact()
                    ->inlineLabel()
                    ->columnSpan(1),
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
                    ->color('gray'),
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
        return '';
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
                    'discount' => $item->discount,
                ])->all(),
                $record->discount,
                $record->tax_type,
                $record->tax_percentage,
                $record->rounding
            );
        }
        return $breakdowns[$record->id];
    }

    protected static function getLineBreakdown($item, $component): array
    {
        $po = $component->getContainer()->getParentComponent()->getRecord();
        return static::getBreakdown($po)['lines'][$item->id] ?? [];
    }

    protected static function getSummary($record): array
    {
        return static::getBreakdown($record)['summary'] ?? [];
    }

    protected static function isInclude12($record): bool
    {
        return $record->tax_type === PurchaseOrderTaxType::INCLUDE
            && $record->tax_percentage === 12.0;
    }

    protected static function formatMoney(float $amount): string
    {
        return
            // 'Rp ' .
            number_format($amount, 2, ',', '.');
    }
}
