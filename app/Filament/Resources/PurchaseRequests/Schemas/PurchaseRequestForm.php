<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
use App\Models\PurchaseRequestItem;
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
use Zvizvi\UserFields\Components\UserEntry;

class PurchaseRequestForm
{
    public $record;

    public static function getOrderedQtyColumnColor(PurchaseRequestItem $purchaseRequestItem): string
    {
        $orderedQty = $purchaseRequestItem->getOrderedQty();
        $requestedQty = (float) $purchaseRequestItem->qty;

        return match (true) {
            $orderedQty == 0 => 'danger',
            $orderedQty < $requestedQty => 'info',
            default => 'success',
        };
    }

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

                            static::itemSection(), // 1.2
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
                        ])
                    ,
                ])
            ,
        ]);
    }

    protected static function dataSection(): Section|string
    {
        return Section::make(__('purchase-request.section.main_info.label'))
            ->icon(Heroicon::ClipboardDocumentList)
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
                    ->schema([
                        TextEntry::make('number')
                            ->hiddenLabel()
                            ->icon(Heroicon::Hashtag)
                            ->iconColor('primary')
                            ->fontFamily(FontFamily::Mono)
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->columnSpanFull()
                        ,
                    ])
                    ->visibleOn('edit')
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
                        TextEntry::make('status')
                            ->hiddenLabel()
                            ->icon(fn($state) => $state?->icon())
                            ->formatStateUsing(fn($state) => $state?->label())
                            ->size(TextSize::Large)
                            ->color(fn($state) => $state?->color())
                            ->badge()
                        ,
                        TextEntry::make('created_at')
                            ->hiddenLabel()
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary')
                            ->date()
                        ,
                    ])
                    ->visibleOn('edit')
                ,

                Section::make(__('purchase-request.fieldset.warehouse_project.label'))
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
                            ->disabledOn('edit')
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
                            ->disabled(fn($get, string $operation) => $operation === 'edit' || blank($get('warehouse_id')))
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
                            ->required()
                            ->disabled(fn($get, string $operation) => $operation === 'edit' || blank($get('company_id')))
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
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} / {$record->po_code} | {$record->name}")
                            ->preload()
                            ->required()
                            ->disabled(
                                fn($get, string $operation) =>
                                $operation === 'edit' || blank($get('warehouse_id')) || blank($get('company_id'))
                            )
                            ->dehydrated()
                        ,
                        Select::make('warehouse_address_id')
                            ->label(__('purchase-request.warehouse_address.label'))
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

                Section::make(__('purchase-request.fieldset.main_info.label'))
                    // ->icon(Heroicon::ClipboardDocumentList)
                    // ->iconColor('primary')
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
                            ->placeholder(__('purchase-request.description.placeholder'))
                            ->helperText(__('purchase-request.description.helper'))
                            ->live()
                            ->autosize()
                            ->columnSpanFull()
                        ,
                        Textarea::make('memo')
                            ->placeholder(__('purchase-request.memo.placeholder'))
                            ->helperText(__('purchase-request.memo.helper'))
                            ->live()
                            ->autosize()
                        ,
                        Textarea::make('boq')
                            ->label(__('purchase-request.boq.label'))
                            ->placeholder(__('purchase-request.boq.placeholder'))
                            ->helperText(__('purchase-request.boq.helper'))
                            ->live()
                            ->autosize()
                        ,
                    ])
                ,
            ])
        ;
    }

    protected static function itemSection(): Section|string
    {
        return Section::make(__('purchase-request.section.purchase_request_items.label'))
            ->icon(Heroicon::Cube)
            ->iconColor('primary')
            ->columnSpanFull()
            ->compact()
            ->schema([
                Repeater::make('purchaseRequestItems')
                    ->label(__('purchase-request.purchase_request_items.label'))
                    ->hiddenLabel()
                    ->relationship()
                    ->columnSpanFull()
                    ->columns([
                        'lg' => 12
                    ])
                    ->schema([
                        Select::make('item_id')
                            ->label(__('item.related.code.label') . ' | ' . __('item.related.name.label'))
                            ->relationship('item', 'name')
                            // ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                            ->searchable(['code', 'name'])
                            ->required()
                            ->columnSpan([
                                'lg' => 7,
                            ])
                        ,
                        TextInput::make('qty')
                            ->numeric()
                            ->minValue(function ($record, $operation) {
                                if ($operation === 'edit' && $record) {
                                    return (float) $record->getOrderedQty();
                                }

                                return 0.01;
                            })
                            ->placeholder(0.01)
                            ->suffix(fn($get) => Item::query()->whereKey($get('item_id'))->value('unit'))
                            ->required()
                            ->columnSpan([
                                'lg' => 3,
                            ])
                        ,
                        TextEntry::make('ordered_qty')
                            ->label(__('purchase-order.purchase_order_item.ordered_qty.label'))
                            ->state(function ($get, $record) {
                                if ($record) {
                                    return number_format($record->getOrderedQty(), 2);
                                }

                                $itemId = $get('id');
                                if (!$itemId) {
                                    return '0.00';
                                }

                                $source = PurchaseRequestItem::query()->find($itemId);

                                return number_format($source?->getOrderedQty() ?? 0, 2);
                            })
                            ->numeric()
                            ->color(fn(PurchaseRequestItem $record): string => self::getOrderedQtyColumnColor($record))
                            ->visible(fn($get): bool => static::showOrderedQty($get('../../status')))
                            ->columnSpan([
                                'lg' => 2,
                            ])
                        ,
                        // TextEntry::make('remaining_qty')
                        //     ->label(__('purchase-order.purchase_order_item.remaining_qty.label'))
                        //     ->state(function ($get, $record) {
                        //         if ($record) {
                        //             return number_format($record->getRemainingQty(), 2);
                        //         }

                        //         $itemId = $get('id');
                        //         if (!$itemId) {
                        //             return '0.00';
                        //         }

                        //         $source = PurchaseRequestItem::query()->find($itemId);

                        //         return number_format($source?->getRemainingQty() ?? 0, 2);
                        //     })
                        //     ->numeric()
                        //     ->color('gray')
                        //     ->visible(fn($get): bool => static::showOrderedQty($get('../../status')))
                        //     ->columnSpan([
                        //         'lg' => 1,
                        //     ])
                        // ,
                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('purchase-request.purchase_request_item.description.placeholder'))
                            ->helperText(__('purchase-request.purchase_request_item.description.helper'))
                            ->autosize()
                            ->columnSpanFull()
                        ,
                    ])
                    ->collapsible()
                    ->reorderable()
                    ->orderColumn('sort')
                    ->itemLabel('#')
                    ->itemNumbers()
                    ->deleteAction(
                        fn(Action $action) => $action
                            ->requiresConfirmation()
                            ->visible(function (array $arguments, Repeater $component): bool {
                                $itemState = $component->getRawState()[$arguments['item']] ?? [];

                                return static::isPurchaseRequestItemDeletable(
                                    itemState: is_array($itemState) ? $itemState : [],
                                );
                            }),
                    )
                    ->minItems(1)
                    ->live()
                ,
            ])
        ;
    }

    protected static function infoSection(): Section|string
    {
        return Section::make(__('purchase-request.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->schema([
                Textarea::make('notes')
                    ->label(__('purchase-request.notes.label'))
                    ->placeholder(__('purchase-request.notes.placeholder'))
                    ->helperText(__('purchase-request.notes.helper'))
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
                    ->label(__('purchase-request.info.label'))
                    ->placeholder(__('purchase-request.info.placeholder'))
                    ->helperText(__('purchase-request.info.helper'))
                    ->autosize()
                    ->required(fn($get, $record) => $record?->hasWatchedFieldChanges($get()) === true)
                    ->disabled(fn($get, $record) => $record?->hasWatchedFieldChanges($get()) === false)
                    ->afterStateHydrated(fn($component) => $component->state(null))
                    ->visible(fn($record, $operation) => $operation === 'edit' && !$record?->hasStatus(PurchaseRequestStatus::DRAFT))
                ,
                TextEntry::make('info')
                    ->label(__('purchase-request.revision_history.label'))
                    ->formatStateUsing(fn($state) => collect(explode("\n", $state))->map(fn($line) => "• " . e($line))->implode('<br>'))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->visible(fn($state, $record) => filled($state) && !$record?->hasStatus(PurchaseRequestStatus::DRAFT))
                ,
            ])
        ;
    }

    public static function isPurchaseRequestItemDeletable(array $itemState = []): bool
    {
        $itemId = $itemState['id'] ?? null;

        if (blank($itemId)) {
            return true;
        }

        $purchaseRequestItem = PurchaseRequestItem::query()->find($itemId);

        if (!$purchaseRequestItem) {
            return true;
        }

        return $purchaseRequestItem->getOrderedQty() <= 0;
    }

    public static function showOrderedQty(PurchaseRequestStatus|int|string|null $status): bool
    {
        if (is_int($status) || is_string($status)) {
            $status = PurchaseRequestStatus::tryFrom((int) $status);
        }

        return
            $status === PurchaseRequestStatus::APPROVED ||
            $status === PurchaseRequestStatus::ORDERED ||
            $status === PurchaseRequestStatus::FINISHED
        ;
    }
}
