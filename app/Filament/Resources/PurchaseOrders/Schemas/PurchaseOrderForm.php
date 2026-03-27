<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use App\Enums\PurchaseOrderStatus;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequestItem;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
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
                    'xl' => 4,
                    '2xl' => 4,
                ])
                ->schema([
                    Grid::make()
                        ->columnSpan(['xl' => 3, '2xl' => 3])
                        ->schema([
                            static::dataSection(),
                            static::itemSection(),
                        ]),
                    Grid::make()
                        ->columnSpan(['xl' => 1, '2xl' => 1])
                        ->schema([
                            static::otherInfoSection(),
                        ]),
                ]),
        ]);
    }

    protected static function dataSection(): Section
    {
        return Section::make('Form ' . __('purchase-order.section.main_info.label'))
            ->icon(Heroicon::ClipboardDocumentList)
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
                        Select::make('vendor_id')
                            ->label(__('vendor.model.label'))
                            ->relationship('vendor', 'name', fn($query) => $query->orderBy('name')->orderBy('code'))
                            ->searchable(['name', 'code'])
                            ->required()
                            ->preload(),
                        Select::make('warehouse_id')
                            ->label(__('warehouse.model.label'))
                            ->relationship('warehouse', 'name')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Select::make('company_id')
                            ->label(__('purchase-order.company.label'))
                            ->relationship('company', 'alias')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Select::make('division_id')
                            ->label(__('division.model.label'))
                            ->relationship('division', 'name')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Select::make('project_id')
                            ->label(__('project.model.label'))
                            ->relationship('project', 'name')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Select::make('warehouse_address_id')
                            ->label(__('purchase-order.warehouse_address.label'))
                            ->relationship('warehouseAddress', 'address')
                            ->searchable()
                            ->placeholder('-')
                            ->preload(),
                    ]),
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
                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('purchase-order.description.placeholder'))
                            ->helperText(__('purchase-order.description.helper'))
                            ->live()
                            ->autosize()
                            ->columnSpanFull(),
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
                    ->columns(4)
                    ->schema([
                        Select::make('purchase_request_item_id')
                            ->label(__('purchase-order.purchase_order_item.source_item.label'))
                            ->getSearchResultsUsing(function (string $search, $get) {
                                $header = [
                                    'warehouse_id' => $get('../../warehouse_id'),
                                    'company_id' => $get('../../company_id'),
                                    'division_id' => $get('../../division_id'),
                                    'project_id' => $get('../../project_id'),
                                ];

                                $header = collect($header)->every(fn($value) => filled($value)) ? $header : null;

                                return PurchaseOrder::getCompatiblePurchaseRequestItemsQuery($header)
                                    ->where(function (Builder $query) use ($search) {
                                        $query->whereHas('item', function ($q) use ($search) {
                                            $q->where('name', 'like', "%{$search}%")
                                                ->orWhere('code', 'like', "%{$search}%");
                                        })
                                            ->orWhereHas('purchaseRequest', function ($q) use ($search) {
                                                $q->where('number', 'like', "%{$search}%");
                                            });
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn($record) => [
                                        $record->id => "{$record->item?->code} | {$record->item?->name} | {$record->purchaseRequest?->number}"
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $record = PurchaseRequestItem::with(['item', 'purchaseRequest'])->find($value);

                                if (!$record)
                                    return null;

                                return "{$record->item?->code} | {$record->item?->name} | {$record->purchaseRequest?->number}";
                            })
                            ->searchable()
                            ->live()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->required()
                            ->columnSpan(2)
                            ->afterStateUpdated(function ($state, $set) {
                                if (!$state) {
                                    return;
                                }

                                $source = PurchaseRequestItem::query()
                                    ->with(['item', 'purchaseRequest'])
                                    ->find($state);

                                if (!$source) {
                                    return;
                                }

                                $set('item_id', $source->item_id);
                                $set('../../warehouse_id', $source->purchaseRequest?->warehouse_id);
                                $set('../../company_id', $source->purchaseRequest?->company_id);
                                $set('../../division_id', $source->purchaseRequest?->division_id);
                                $set('../../project_id', $source->purchaseRequest?->project_id);
                                $set('../../warehouse_address_id', $source->purchaseRequest?->warehouse_address_id);
                            })
                        ,
                        Hidden::make('item_id')->required(),
                        TextInput::make('qty')
                            ->label(__('purchase-order.purchase_order_item.qty.label'))
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->suffix(function ($get) {
                                $source = PurchaseRequestItem::query()->with('item:id,unit')->find($get('purchase_request_item_id'));
                                return $source?->item?->unit;
                            })
                            ->rule(function ($get, $record) {
                                return function (string $attribute, $value, $fail) use ($get, $record) {
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
                            ->default(0)
                            ->required()
                            ->columnSpan(1),
                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('purchase-order.purchase_order_item.description.placeholder'))
                            ->helperText(__('purchase-order.purchase_order_item.description.helper'))
                            ->autosize()
                            ->columnSpanFull(),
                        Placeholder::make('source_context')
                            ->label(__('purchase-order.purchase_order_item.source_item.context'))
                            ->columnSpan(6)
                            ->content(function ($get) {
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
                                    'allocated_qty' => number_format($source->getAllocatedQty(), 2),
                                    'remaining_qty' => number_format($source->getRemainingQty(), 2),
                                ]);
                            }),
                    ])
                    ->reorderable()
                    ->orderColumn('sort')
                    ->itemNumbers()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $subtotal = collect($get('purchaseOrderItems'))
                            ->sum(fn($item) => (float) ($item['qty'] ?? 0) * (float) ($item['price'] ?? 0));
                        $set('subtotal', $subtotal);
                    })
                    ->afterStateHydrated(function ($state, $set, $get) {
                        $subtotal = collect($get('purchaseOrderItems'))
                            ->sum(fn($item) => (float) ($item['qty'] ?? 0) * (float) ($item['price'] ?? 0));
                        $set('subtotal', $subtotal);
                    })
                    ->deleteAction(fn(Action $action) => $action->requiresConfirmation())
                    ->minItems(1)
                    ->live(),
                TextInput::make('subtotal')
                    ->label('Subtotal')
                    ->numeric()
                    ->readOnly()
                    ->prefix('Rp')
                    ->dehydrated(false),
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
                TextInput::make('termin')
                    ->placeholder(__('purchase-order.termin.placeholder'))
                    ->helperText(__('purchase-order.termin.helper')),
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
                            ->map(fn($line) => '• ' . e($line))
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
}
