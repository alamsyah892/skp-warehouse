<?php

namespace App\Filament\Resources\GoodsIssues\Schemas;

use App\Enums\GoodsIssueStatus;
use App\Enums\GoodsIssueType;
use App\Models\GoodsIssueItem;
use App\Models\Item;
use App\Models\Project;
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

class GoodsIssueForm
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
                        ]),
                ]),
        ]);
    }

    protected static function dataSection(): Section|string
    {
        return Section::make(__('goods-issue.section.main_info.label'))
            ->icon(Heroicon::RectangleStack)
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
                            ->visibleOn('edit'),
                        TextEntry::make('type')
                            ->hiddenLabel()
                            ->icon(fn($state) => $state?->icon())
                            ->formatStateUsing(fn($state) => $state?->label())
                            ->size(TextSize::Large)
                            ->color(fn($state) => $state?->color())
                            ->badge()
                            ->visibleOn('edit'),
                        Select::make('type')
                            ->label(__('goods-issue.type.label'))
                            ->options(GoodsIssueType::options())
                            ->native(false)
                            ->live()
                            ->required()
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
                            ->alignEnd()
                            ->visibleOn('edit'),
                    ]),
                Section::make(__('goods-issue.fieldset.warehouse_project.label'))
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
                            ->afterStateUpdated(function ($set): void {
                                $set('company_id', null);
                                $set('division_id', null);
                                $set('project_id', null);
                                $set('warehouse_address_id', null);
                                $set('goodsIssueItems', []);
                            })
                            ->required()
                            ->disabledOn('edit')
                            ->dehydrated(),
                        Select::make('company_id')
                            ->label(__('goods-issue.company.label'))
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
                                        ->orderBy('code');
                                },
                            )
                            ->searchable(['alias', 'code'])
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($set): void {
                                $set('division_id', null);
                                $set('project_id', null);
                                $set('goodsIssueItems', []);
                            })
                            ->required()
                            ->disabled(fn($get, string $operation) => $operation === 'edit' || blank($get('warehouse_id')))
                            ->dehydrated(),
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
                                        ->orderBy('code');
                                },
                            )
                            ->searchable(['name', 'code'])
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn($set) => $set('goodsIssueItems', []))
                            ->required()
                            ->disabled(fn($get, string $operation) => $operation === 'edit' || blank($get('company_id')))
                            ->dehydrated(),
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
                                        ->orderBy('name')
                                        ->orderBy('code');
                                }
                            )
                            ->searchable(['name', 'code', 'po_code'])
                            ->getOptionLabelFromRecordUsing(fn(Project $record) => "{$record->code} / {$record->po_code} | {$record->name}")
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn($set) => $set('goodsIssueItems', []))
                            ->required()
                            ->disabled(
                                fn($get, string $operation) =>
                                $operation === 'edit' || blank($get('warehouse_id')) || blank($get('company_id'))
                            )
                            ->dehydrated(),
                        Select::make('warehouse_address_id')
                            ->label(__('goods-issue.warehouse_address.label'))
                            ->relationship(
                                'warehouseAddress',
                                'address',
                                fn($query, $get) => $query->where('warehouse_id', $get('warehouse_id')),
                            )
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->address} - {$record->city}")
                            ->searchable()
                            ->preload()
                            ->default(null)
                            ->disabled(fn($get) => blank($get('warehouse_id')))
                            ->live()
                            ->columnSpanFull(),
                    ]),
                Section::make(__('goods-issue.fieldset.main_info.label'))
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->columns([
                        'default' => 1,
                    ])
                    ->compact()
                    ->contained(false)
                    ->schema([
                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('goods-issue.description.placeholder'))
                            ->helperText(__('goods-issue.description.helper'))
                            ->live(debounce: 500)
                            ->autosize()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function itemSection(): Section|string
    {
        return Section::make(__('goods-issue.section.goods_issue_items.label'))
            ->icon(Heroicon::Cube)
            ->iconColor('primary')
            ->compact()
            ->schema([
                Repeater::make('goodsIssueItems')
                    ->label(__('goods-issue.goods_issue_items.label'))
                    ->hiddenLabel()
                    ->relationship()
                    ->columns([
                        'lg' => 12,
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
                                    ->options(fn($get, $record): array => GoodsIssueItem::getSelectableOptions(
                                        static::getHeaderFromState($get),
                                        static::getCurrentGoodsIssueId($record),
                                    ))
                                    ->searchable()
                                    ->getSearchResultsUsing(fn(string $search, $get, $record): array => GoodsIssueItem::getSelectableOptions(
                                        static::getHeaderFromState($get),
                                        static::getCurrentGoodsIssueId($record),
                                        $search,
                                    ))
                                    ->hint(function ($get, $record): string {
                                        $itemId = (int) ($get('item_id') ?? 0);
                                        $detail = GoodsIssueItem::getAvailabilityDetail(
                                            static::getHeaderFromState($get),
                                            $itemId,
                                            static::getCurrentGoodsIssueId($record),
                                        );

                                        if (!$detail) {
                                            return '';
                                        }

                                        return __('goods-issue.stock_item.context_value', [
                                            'received_qty' => number_format($detail['received_qty'], 2),
                                            'issued_qty' => number_format($detail['issued_qty'], 2),
                                            'available_qty' => number_format($detail['available_qty'], 2),
                                        ]);
                                    })
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get, $record): void {
                                        $itemId = (int) $state;

                                        if ($itemId <= 0) {
                                            $set('qty', null);
                                            return;
                                        }

                                        $availableQty = GoodsIssueItem::getAvailableQtyForItem(
                                            static::getHeaderFromState($get),
                                            $itemId,
                                            static::getCurrentGoodsIssueId($record),
                                        );

                                        if ($availableQty > 0) {
                                            $set('qty', $availableQty);
                                        }
                                    })
                                    ->disabled(fn($operation, $get): bool => $operation === 'edit' || !static::isHeaderReady(static::getHeaderFromState($get)))
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->dehydrated()
                                    ->columnSpanFull(),
                                Textarea::make('description')
                                    ->label(__('common.description.label'))
                                    ->placeholder(__('goods-issue.goods_issue_item.description.placeholder'))
                                    ->helperText(__('goods-issue.goods_issue_item.description.helper'))
                                    ->autosize(),
                            ]),
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
                                TextEntry::make('available_qty')
                                    ->label(__('goods-issue.goods_issue_item.available_qty.label'))
                                    ->state(function ($get, $record): float {
                                        $itemId = (int) ($get('item_id') ?? 0);

                                        return GoodsIssueItem::getAvailableQtyForItem(
                                            static::getHeaderFromState($get),
                                            $itemId,
                                            static::getCurrentGoodsIssueId($record),
                                        );
                                    })
                                    ->numeric()
                                    ->color('primary')
                                    ->columnSpan([
                                        'default' => 1,
                                        'lg' => 2,
                                    ]),
                                TextInput::make('qty')
                                    ->numeric()
                                    ->placeholder(0.01)
                                    ->suffix(fn($get): ?string => Item::query()->whereKey($get('item_id'))->value('unit'))
                                    ->minValue(0.01)
                                    ->required()
                                    ->live(debounce: 500)
                                    ->rule(function ($get, $record) {
                                        return function (string $attribute, $value, $fail) use ($get, $record): void {
                                            $itemId = (int) ($get('item_id') ?? 0);

                                            if ($itemId <= 0) {
                                                return;
                                            }

                                            $availableQty = GoodsIssueItem::getAvailableQtyForItem(
                                                static::getHeaderFromState($get),
                                                $itemId,
                                                static::getCurrentGoodsIssueId($record),
                                            );

                                            if ((float) $value > $availableQty) {
                                                $fail(__('goods-issue.validation.qty_exceeded', [
                                                    'available' => number_format($availableQty, 2),
                                                ]));
                                            }
                                        };
                                    })
                                    ->columnSpan([
                                        'default' => 1,
                                        'lg' => 2,
                                    ]),
                            ]),
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
        return Section::make(__('goods-issue.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->schema([
                Textarea::make('notes')
                    ->label(__('goods-issue.notes.label'))
                    ->placeholder(__('goods-issue.notes.placeholder'))
                    ->helperText(__('goods-issue.notes.helper'))
                    ->autosize(),
                UserEntry::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->color('gray')
                    ->visibleOn('edit'),
                TextEntry::make('updated_at')->date()
                    ->label(__('common.updated_at.label'))
                    ->size(TextSize::Small)
                    ->color('gray')
                    ->visibleOn('edit'),
                TextEntry::make('deleted_at')->date()
                    ->label(__('common.deleted_at.label'))
                    ->size(TextSize::Small)
                    ->color('gray')
                    ->visible(fn($state) => $state != null),
                Textarea::make('info')
                    ->label(__('goods-issue.info.label'))
                    ->placeholder(__('goods-issue.info.placeholder'))
                    ->helperText(__('goods-issue.info.helper'))
                    ->autosize()
                    ->required(fn($record, $livewire): bool => $record?->hasWatchedFieldChangesFromState((array) ($livewire->data ?? [])) === true)
                    ->disabled(fn($record, $livewire): bool => $record?->hasWatchedFieldChangesFromState((array) ($livewire->data ?? [])) !== true)
                    ->afterStateHydrated(fn($component) => $component->state(null))
                    ->afterStateUpdated(function ($set, $record, $livewire): void {
                        if ($record?->hasWatchedFieldChangesFromState((array) ($livewire->data ?? [])) !== true) {
                            $set('info', null);
                        }
                    })
                    ->dehydrated(fn($record, $livewire): bool => $record?->hasWatchedFieldChangesFromState((array) ($livewire->data ?? [])) === true)
                    ->visible(fn($record, $operation) => $operation === 'edit' && !$record?->hasStatus(GoodsIssueStatus::CANCELED)),
                TextEntry::make('info')
                    ->label(__('goods-issue.revision_history.label'))
                    ->formatStateUsing(fn($state) => collect(explode("\n", $state))->map(fn($line) => '&#8226; ' . e($line))->implode('<br>'))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->visible(fn($state) => filled($state)),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getHeaderFromState(callable $get): array
    {
        return [
            'warehouse_id' => $get('../../warehouse_id') ?? $get('warehouse_id'),
            'company_id' => $get('../../company_id') ?? $get('company_id'),
            'division_id' => $get('../../division_id') ?? $get('division_id'),
            'project_id' => $get('../../project_id') ?? $get('project_id'),
        ];
    }

    protected static function getCurrentGoodsIssueId(mixed $record): ?int
    {
        return $record?->goods_issue_id ? (int) $record->goods_issue_id : null;
    }

    /**
     * @param  array<string, mixed>  $header
     */
    protected static function isHeaderReady(array $header): bool
    {
        return collect([
            (int) ($header['warehouse_id'] ?? 0),
            (int) ($header['company_id'] ?? 0),
            (int) ($header['division_id'] ?? 0),
            (int) ($header['project_id'] ?? 0),
        ])->every(fn(int $value): bool => $value > 0);
    }
}
