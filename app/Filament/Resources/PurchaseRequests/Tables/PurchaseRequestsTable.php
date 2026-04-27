<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use App\Models\PurchaseRequest;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Zvizvi\UserFields\Components\UserColumn;

class PurchaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label(__('purchase-request.number.label'))
                    ->wrapHeader()
                    ->description(fn($record): ?string => Str::limit($record->description, 20))
                    ->tooltip(fn($record): string => $record->description)
                    ->searchable(['number', 'description'])
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    // ->size(TextSize::Large)
                    ->fontFamily(FontFamily::Mono)
                    ->verticallyAlignStart()
                    ->wrap(false)
                ,
                TextColumn::make('status')
                    ->formatStateUsing(fn($state) => $state?->label())
                    ->icon(fn($state) => $state?->icon())
                    ->color(fn($state) => $state?->color())
                    ->alignCenter()
                    ->badge()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                ,
                TextColumn::make('created_at')
                    ->label(__('common.created_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                ,

                TextColumn::make('warehouse.name')
                    ->label(__('warehouse.model.label'))
                    ->wrapHeader()
                    ->verticallyAlignStart()
                    ->wrap()
                ,
                TextColumn::make('company.alias')
                    ->label(__('purchase-order.company.label'))
                    ->wrapHeader()
                    ->verticallyAlignStart()
                    ->wrap()
                ,
                TextColumn::make('division.name')
                    ->label(__('division.model.label'))
                    ->wrapHeader()
                    ->verticallyAlignStart()
                    ->wrap()
                ,
                TextColumn::make('project.name')
                    ->label(__('project.model.label'))
                    ->wrapHeader()
                    ->verticallyAlignStart()
                    ->wrap()
                ,
                TextColumn::make('warehouseAddress.address')
                    ->label(__('purchase-request.warehouse_address.label'))
                    ->wrapHeader()
                    ->limit(20)
                    ->tooltip(fn($state) => $state)
                    ->searchable()
                    ->placeholder('-')
                    ->color('gray')
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,

                UserColumn::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->verticallyAlignStart()
                    ->wrap(false)
                    ->wrapped(false)
                ,

                TextColumn::make('memo')
                    ->limit(20)
                    ->tooltip(fn($state) => $state)
                    ->searchable()
                    ->placeholder('-')
                    ->color('gray')
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('boq')
                    ->label(__('purchase-request.boq.label'))
                    ->wrapHeader()
                    ->limit(20)
                    ->tooltip(fn($state) => $state)
                    ->searchable()
                    ->placeholder('-')
                    ->color('gray')
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,

                TextColumn::make('purchase_request_items_count')
                    ->label(__('purchase-request.purchase_request_items.count_label'))
                    ->wrapHeader()
                    ->sortable()
                    ->color('gray')
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('purchase_request_items_sum_qty')
                    ->label(__('purchase-request.purchase_request_items.total_qty_label'))
                    ->wrapHeader()
                    ->formatStateUsing(fn($state): string => number_format((float) $state, 2))
                    ->alignEnd()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('purchase_request_items_ordered_qty_sum')
                    ->label(__('purchase-request.purchase_request_items.ordered_qty_label'))
                    ->wrapHeader()
                    ->formatStateUsing(fn($state): string => number_format((float) $state, 2))
                    ->color('gray')
                    ->alignEnd()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('purchase_request_items_ordered_percentage')
                    ->label(__('purchase-request.purchase_request_items.ordered_percentage_label'))
                    ->wrapHeader()
                    ->formatStateUsing(fn($state): string => number_format((float) $state, 2) . '%')
                    ->color(fn(PurchaseRequest $record): string => match (true) {
                        $record->getOrderedPercentage() <= 0.0 => 'danger',
                        $record->getOrderedPercentage() < 100.0 => 'warning',
                        default => 'success',
                    })
                    ->badge()
                    ->alignCenter()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('purchase_orders_count')
                    ->label(__('purchase-request.purchase_orders.count_label'))
                    ->wrapHeader()
                    ->sortable()
                    ->color('gray')
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,

                TextColumn::make('updated_at')
                    ->label(__('common.updated_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->color('gray')
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('deleted_at')
                    ->label(__('common.deleted_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->color('gray')
                    ->placeholder('-')
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
            ])
            ->filters([
                SelectFilter::make('warehouse')
                    ->label(__('warehouse.model.label'))
                    ->relationship(
                        'warehouse',
                        'name',
                        fn($query) => $query
                            ->when(
                                auth()->user()->warehouses()->exists(),
                                fn($q) => $q->whereIn('warehouses.id', auth()->user()->warehouses->pluck('id'))
                            )
                            ->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                ,
                SelectFilter::make('company')
                    ->label(__('purchase-request.company.label'))
                    ->relationship(
                        'company',
                        'alias',
                        fn($query) => $query->orderBy('alias')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                ,
                SelectFilter::make('division')
                    ->label(__('division.model.label'))
                    ->relationship(
                        'division',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                ,
                SelectFilter::make('project')
                    ->label(__('project.model.label'))
                    ->relationship(
                        'project',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                    ->searchable(['code', 'name'])
                    ->multiple()
                    ->preload()
                ,

                TrashedFilter::make()->native(false),
            ])
            ->recordActions([
                ViewAction::make()->hiddenLabel(),
            ], position: RecordActionsPosition::BeforeColumns)

            ->striped()
            ->stackedOnMobile()

            ->contentGrid([])
            ->paginated([5, 10, 25, 50, 100])
            ->paginationMode(PaginationMode::Default)
            ->defaultPaginationPageOption(10)
        ;
    }
}
