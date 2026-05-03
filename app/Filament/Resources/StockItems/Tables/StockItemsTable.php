<?php

namespace App\Filament\Resources\StockItems\Tables;

use App\Models\Company;
use App\Models\Division;
use App\Models\Project;
use App\Models\Warehouse;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
}
