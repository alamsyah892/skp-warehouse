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
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->fontFamily(FontFamily::Mono)
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
                            ->alignEnd()
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
                            ->disabledOn('edit')
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
                                        ->when(
                                            $companyId && $warehouseId,
                                            fn($q) => $q->where(function ($qq) use ($companyId, $warehouseId) {
                                                $qq
                                                    ->whereHas('companies', fn($q2) => $q2->where('companies.id', $companyId))
                                                    ->orWhereHas('warehouses', fn($q2) => $q2->where('warehouses.id', $warehouseId));
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
                            ->live(debounce: 500)
                            ->autosize()
                            ->columnSpanFull()
                        ,
                        Textarea::make('memo')
                            ->placeholder(__('purchase-request.memo.placeholder'))
                            ->helperText(__('purchase-request.memo.helper'))
                            ->live(debounce: 500)
                            ->autosize()
                        ,
                        Textarea::make('boq')
                            ->label(__('purchase-request.boq.label'))
                            ->placeholder(__('purchase-request.boq.placeholder'))
                            ->helperText(__('purchase-request.boq.helper'))
                            ->live(debounce: 500)
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
                        Grid::make()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 8,
                            ])
                            ->columns(1)
                            ->schema([
                                Select::make('item_id')
                                    ->label(__('item.code.label') . ' | ' . __('item.name.label'))
                                    ->relationship('item', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record): string => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->required()
                                    ->disabled(fn($record): bool => $record?->getOrderedQty() ?? 0 > 0)
                                    ->live()
                                    ->dehydrated()
                                ,
                                Textarea::make('description')
                                    ->label(__('common.description.label'))
                                    ->placeholder(__('purchase-request.purchase_request_item.description.placeholder'))
                                    ->helperText(__('purchase-request.purchase_request_item.description.helper'))
                                    ->autosize()
                                ,
                            ])
                        ,
                        Grid::make()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 4,
                            ])
                            ->columns([
                                'default' => 2,
                                'lg' => 2,
                            ])
                            ->schema([
                                TextInput::make('qty')
                                    ->numeric()
                                    ->placeholder(0.01)
                                    ->suffix(fn($get): string|null => Item::query()->whereKey($get('item_id'))->value('unit'))
                                    ->minValue(fn($record): float => $record?->getOrderedQty() > 0 ? (float) $record?->getOrderedQty() : 0.01)
                                    ->required()
                                    ->columnSpan([
                                        'default' => 1,
                                        'lg' => 2,
                                    ])
                                ,
                                TextEntry::make('ordered_qty')
                                    ->label(__('purchase-order.purchase_order_item.ordered_qty.label'))
                                    ->state(fn($record): float => $record?->getOrderedQty() ?? 0)
                                    ->numeric()
                                    ->color(fn($record): string => $record?->getOrderedQtyColor() ?? 'gray')
                                    // ->visible(fn($record): bool => $record?->getOrderedQty() ?? 0 > 0)
                                    ->columnSpan([
                                        'default' => 1,
                                        'lg' => 2,
                                    ])
                                ,
                            ])
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

                                return static::isItemDeletable(
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
                    ->required(fn($record, $livewire): bool => $record?->hasWatchedFieldChangesFromState((array) ($livewire->data ?? [])) === true)
                    ->disabled(fn($record, $livewire): bool => $record?->hasWatchedFieldChangesFromState((array) ($livewire->data ?? [])) !== true)
                    ->afterStateHydrated(fn($component) => $component->state(null))
                    ->visible(fn($record, $operation) => $operation === 'edit' && !$record?->hasStatus(PurchaseRequestStatus::DRAFT))
                ,
                TextEntry::make('info')
                    ->label(__('purchase-request.revision_history.label'))
                    ->formatStateUsing(fn($state) => collect(explode("\n", $state))->map(fn($line) => "• " . $line)->implode('<br>'))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->visible(fn($state, $record) => filled($state) && !$record?->hasStatus(PurchaseRequestStatus::DRAFT))
                ,
            ])
        ;
    }

    protected static function isItemDeletable(array $itemState = []): bool
    {
        $itemId = $itemState['id'] ?? null;

        if (blank($itemId)) {
            return true;
        }

        $item = PurchaseRequestItem::query()->find($itemId);

        if (!$item) {
            return true;
        }

        return $item->getOrderedQty() == 0;
    }
}
