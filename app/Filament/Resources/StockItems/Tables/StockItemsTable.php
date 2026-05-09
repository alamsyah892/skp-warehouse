<?php

namespace App\Filament\Resources\StockItems\Tables;

use App\Filament\Resources\StockItems\Support\StockItemMutationData;
use App\Models\Company;
use App\Models\Division;
use App\Models\GoodsReceiveItem;
use App\Models\Project;
use App\Models\Warehouse;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class StockItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('item_code')
                    ->label(__('item.model.label'))
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->badge()
                    ->color('primary')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $builder) use ($search): void {
                            $builder
                                ->where('items.code', 'like', "%{$search}%")
                                ->orWhere('items.name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable()
                    ->description(fn($record): string => $record->item_name)
                    ->wrap()
                ,
                TextColumn::make('project_name')
                    ->label(__('project.warehouse_project.label'))
                    ->description(fn($record): string => Str::limit("{$record->warehouse_name} - {$record->company_alias} - {$record->division_name}", 36))
                    ->tooltip(fn($record): string => "{$record->warehouse_name} - {$record->company_alias} - {$record->division_name} - {$record->project_name}")
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $builder) use ($search): void {
                            $builder
                                ->where('projects.name', 'like', "%{$search}%")
                                ->orWhere('projects.code', 'like', "%{$search}%")
                                ->orWhere('projects.po_code', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: fn(Builder $query, string $direction): Builder => $query->orderBy('projects.name', $direction))
                ,
                TextColumn::make('item_unit')
                    ->label(__('item.unit.label'))
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                ,
                TextColumn::make('received_qty')
                    ->label(__('stock-item.qty.received'))
                    ->numeric(2)
                    ->alignEnd()
                    ->color('success')
                    ->sortable()
                ,
                TextColumn::make('issued_qty')
                    ->label(__('stock-item.qty.issued'))
                    ->numeric(2)
                    ->alignEnd()
                    ->color('danger')
                    ->sortable()
                ,
                TextColumn::make('available_qty')
                    ->label(__('stock-item.qty.available'))
                    ->numeric(2)
                    ->weight(FontWeight::Bold)
                    ->alignEnd()
                    ->badge()
                    ->color('warning')
                    ->sortable()
                ,
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label(__('warehouse.model.label'))
                    ->options(
                        fn(): array => Warehouse::query()
                            ->when(
                                auth()->user()->warehouses()->exists(),
                                fn($query) => $query->whereIn('warehouses.id', auth()->user()->warehouses->pluck('id'))
                            )
                            ->orderBy('name')
                            ->orderBy('code')
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        $values = $data['values'] ?? [];

                        if (blank($values)) {
                            return $query;
                        }

                        return $query->whereIn('goods_receives.warehouse_id', $values);
                    })
                ,
                SelectFilter::make('company_id')
                    ->label(__('company.model.label'))
                    ->options(fn(): array => Company::query()->orderBy('alias')->orderBy('code')->pluck('alias', 'id')->toArray())
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        $values = $data['values'] ?? [];

                        if (blank($values)) {
                            return $query;
                        }

                        return $query->whereIn('goods_receives.company_id', $values);
                    })
                ,
                SelectFilter::make('division_id')
                    ->label(__('division.model.label'))
                    ->options(fn(): array => Division::query()->orderBy('name')->orderBy('code')->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        $values = $data['values'] ?? [];

                        if (blank($values)) {
                            return $query;
                        }

                        return $query->whereIn('goods_receives.division_id', $values);
                    })
                ,
                SelectFilter::make('project_id')
                    ->label(__('project.model.label'))
                    ->options(
                        fn(): array => Project::query()
                            ->orderBy('name')
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn(Project $project): array => [
                                (string) $project->id => "{$project->code} / {$project->po_code} | {$project->name}",
                            ])
                            ->toArray()
                    )
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        $values = $data['values'] ?? [];

                        if (blank($values)) {
                            return $query;
                        }

                        return $query->whereIn('goods_receives.project_id', $values);
                    })
                ,
                Filter::make('low_stock')
                    ->label(__('stock-item.filters.low_stock'))
                    ->query(fn(Builder $query): Builder => $query->havingRaw('available_qty <= 10'))
                    ->toggle()
                ,
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                static::viewMutationsAction(),
            ], position: RecordActionsPosition::BeforeColumns)
            ->defaultSort('available_qty', 'asc')
            ->searchDebounce('700ms')
            ->striped()
            ->stackedOnMobile()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->paginationMode(PaginationMode::Default)
            ->emptyStateHeading(__('stock-item.empty.heading'))
            ->emptyStateDescription(__('stock-item.empty.description'))
        ;
    }

    protected static function viewMutationsAction(): Action
    {
        return Action::make('viewMutations')
            ->label(__('stock-item.mutation.action.label'))
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('gray')
            ->slideOver()
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('actions.close'))
            ->modalHeading(fn (GoodsReceiveItem $record): string => __('stock-item.mutation.heading', ['item' => $record->item_code]))
            ->schema([
                Section::make(__('stock-item.mutation.filter_section.label'))
                    ->description(__('stock-item.mutation.filter_section.description'))
                    ->compact()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Select::make('year')
                            ->label(__('stock-item.mutation.filters.year'))
                            ->options(fn (GoodsReceiveItem $record): array => StockItemMutationData::getYearOptions($record))
                            ->placeholder(__('stock-item.mutation.filters.placeholder'))
                            ->native(false)
                            ->live(),
                        Select::make('month')
                            ->label(__('stock-item.mutation.filters.month'))
                            ->options(static::monthOptions())
                            ->placeholder(__('stock-item.mutation.filters.placeholder'))
                            ->native(false)
                            ->live(),
                    ]),
                Section::make(__('stock-item.mutation.context_section.label'))
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
                                'md' => 2,
                            ])
                            ->schema([
                                Placeholder::make('item_code')
                                    ->label(__('item.code.label'))
                                    ->content(fn (GoodsReceiveItem $record): HtmlString => new HtmlString(static::renderMonoBadge($record->item_code))),
                                Placeholder::make('item_name')
                                    ->label(__('item.name.label'))
                                    ->content(fn (GoodsReceiveItem $record): string => $record->item_name),
                                Placeholder::make('item_unit')
                                    ->label(__('item.unit.label'))
                                    ->content(fn (GoodsReceiveItem $record): string => $record->item_unit),
                                Placeholder::make('period')
                                    ->label(__('stock-item.mutation.period.label'))
                                    ->content(fn (GoodsReceiveItem $record, Get $get): string => static::getMutationSummary($record, $get)['period_label']),
                            ]),
                        Grid::make()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 5,
                            ])
                            ->columns(1)
                            ->schema([
                                Placeholder::make('warehouse_context')
                                    ->label(__('project.warehouse_project.label'))
                                    ->content(fn (GoodsReceiveItem $record): string => "{$record->warehouse_name} - {$record->company_alias} - {$record->division_name}"),
                                Placeholder::make('project_context')
                                    ->label(__('project.model.label'))
                                    ->content(fn (GoodsReceiveItem $record): string => collect([
                                        $record->project_code,
                                        $record->project_po_code,
                                        $record->project_name,
                                    ])->filter()->join(' / ')),
                            ]),
                    ]),
                Section::make(__('stock-item.mutation.summary_section.label'))
                    ->compact()
                    ->columns([
                        'default' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        Placeholder::make('opening_balance')
                            ->label(__('stock-item.mutation.summary.opening_balance'))
                            ->content(fn (GoodsReceiveItem $record, Get $get): HtmlString => new HtmlString(
                                static::renderQuantityValue(static::getMutationSummary($record, $get)['opening_balance'], 'gray')
                            )),
                        Placeholder::make('total_received')
                            ->label(__('stock-item.mutation.summary.total_received'))
                            ->content(fn (GoodsReceiveItem $record, Get $get): HtmlString => new HtmlString(
                                static::renderQuantityValue(static::getMutationSummary($record, $get)['total_received'], 'success')
                            )),
                        Placeholder::make('total_issued')
                            ->label(__('stock-item.mutation.summary.total_issued'))
                            ->content(fn (GoodsReceiveItem $record, Get $get): HtmlString => new HtmlString(
                                static::renderQuantityValue(static::getMutationSummary($record, $get)['total_issued'], 'danger')
                            )),
                        Placeholder::make('ending_balance')
                            ->label(__('stock-item.mutation.summary.ending_balance'))
                            ->content(fn (GoodsReceiveItem $record, Get $get): HtmlString => new HtmlString(
                                static::renderQuantityValue(static::getMutationSummary($record, $get)['ending_balance'], 'warning')
                            )),
                    ]),
                Section::make(__('stock-item.mutation.table_section.label'))
                    ->description(fn (GoodsReceiveItem $record, Get $get): string => __('stock-item.mutation.table_section.description', [
                        'period' => static::getMutationSummary($record, $get)['period_label'],
                    ]))
                    ->compact()
                    ->schema([
                        Placeholder::make('mutations_table')
                            ->hiddenLabel()
                            ->content(fn (GoodsReceiveItem $record, Get $get): HtmlString => new HtmlString(
                                static::renderMutationTable(static::getMutationSummary($record, $get)['mutations'])
                            )),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected static function monthOptions(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [
                (string) $month => now()->setMonth($month)->translatedFormat('F'),
            ])
            ->all();
    }

    /**
     * @return array{
     *     period_label: string,
     *     opening_balance: float,
     *     total_received: float,
     *     total_issued: float,
     *     ending_balance: float,
     *     mutations: array<int, array{
     *         transaction_date: string,
     *         document_type: string,
     *         document_type_label: string,
     *         document_number: string,
     *         description: string,
     *         qty_in: float,
     *         qty_out: float,
     *         balance: float
     *     }>
     * }
     */
    protected static function getMutationSummary(GoodsReceiveItem $record, Get $get): array
    {
        return StockItemMutationData::getSummary(
            $record,
            filled($get('year')) ? (int) $get('year') : null,
            filled($get('month')) ? (int) $get('month') : null,
        );
    }

    protected static function renderMonoBadge(string $value): string
    {
        return sprintf(
            '<span class="fi-badge inline-flex items-center gap-1 rounded-md bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30"><span class="font-mono">%s</span></span>',
            e($value),
        );
    }

    protected static function renderQuantityValue(float $value, string $tone): string
    {
        $classes = match ($tone) {
            'success' => 'text-success-600 dark:text-success-400',
            'danger' => 'text-danger-600 dark:text-danger-400',
            'warning' => 'text-warning-600 dark:text-warning-400',
            default => 'text-gray-600 dark:text-gray-400',
        };

        return sprintf(
            '<div class="text-right text-lg font-semibold %s">%s</div>',
            $classes,
            number_format($value, 2),
        );
    }

    /**
     * @param  array<int, array{
     *     transaction_date: string,
     *     document_type: string,
     *     document_type_label: string,
     *     document_number: string,
     *     description: string,
     *     qty_in: float,
     *     qty_out: float,
     *     balance: float
     * }>  $mutations
     */
    protected static function renderMutationTable(array $mutations): string
    {
        if ($mutations === []) {
            return sprintf(
                '<div class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">%s</div>',
                e(__('stock-item.mutation.empty')),
            );
        }

        $rows = collect($mutations)
            ->map(function (array $mutation): string {
                return sprintf(
                    '<tr class="border-b border-gray-100 align-top last:border-0 dark:border-gray-800">
                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">%s</td>
                        <td class="px-3 py-2 text-sm">
                            <div class="font-medium text-gray-900 dark:text-white">%s</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">%s</div>
                        </td>
                        <td class="px-3 py-2 text-sm font-mono text-gray-900 dark:text-white">%s</td>
                        <td class="px-3 py-2 text-sm whitespace-pre-line text-gray-600 dark:text-gray-300">%s</td>
                        <td class="px-3 py-2 text-right text-sm font-semibold text-success-600 dark:text-success-400">%s</td>
                        <td class="px-3 py-2 text-right text-sm font-semibold text-danger-600 dark:text-danger-400">%s</td>
                        <td class="px-3 py-2 text-right text-sm font-semibold text-warning-600 dark:text-warning-400">%s</td>
                    </tr>',
                    e($mutation['transaction_date']),
                    e($mutation['document_type']),
                    e($mutation['document_type_label']),
                    e($mutation['document_number']),
                    e($mutation['description'] ?: '-'),
                    number_format($mutation['qty_in'], 2),
                    number_format($mutation['qty_out'], 2),
                    number_format($mutation['balance'], 2),
                );
            })
            ->implode('');

        return sprintf(
            '<div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th class="px-3 py-3">%s</th>
                                <th class="px-3 py-3">%s</th>
                                <th class="px-3 py-3">%s</th>
                                <th class="px-3 py-3">%s</th>
                                <th class="px-3 py-3 text-right">%s</th>
                                <th class="px-3 py-3 text-right">%s</th>
                                <th class="px-3 py-3 text-right">%s</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-transparent">%s</tbody>
                    </table>
                </div>
            </div>',
            e(__('common.created_at.label')),
            e(__('stock-item.mutation.table.document_type')),
            e(__('common.number.label')),
            e(__('common.description.label')),
            e(__('stock-item.qty.received')),
            e(__('stock-item.qty.issued')),
            e(__('stock-item.mutation.table.balance')),
            $rows,
        );
    }
}
