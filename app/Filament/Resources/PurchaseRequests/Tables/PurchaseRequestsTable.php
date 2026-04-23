<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Zvizvi\UserFields\Components\UserColumn;

class PurchaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label(__('purchase-request.number.label'))
                    ->description(fn($record): HtmlString => new HtmlString(nl2br($record->description)))
                    ->searchable()
                    ->sortable()
                    ->fontFamily(FontFamily::Mono)
                    ->size(TextSize::Large)
                    ->weight(FontWeight::Bold)
                    ->verticallyAlignStart()
                    ->wrap()
                ,
                TextColumn::make('warehouse.name')
                    ->label(__('warehouse.model.label'))
                    ->verticallyAlignStart()
                    ->wrap()
                ,
                TextColumn::make('company.alias')
                    ->label(__('purchase-request.company.label'))
                    ->wrapHeader()
                    ->verticallyAlignStart()
                    ->wrap()
                ,
                TextColumn::make('division.name')
                    ->label(__('division.model.label'))
                    ->verticallyAlignStart()
                    ->wrap()
                ,
                TextColumn::make('project.name')
                    ->label(__('project.model.label'))
                    ->verticallyAlignStart()
                    ->wrap()
                ,
                TextColumn::make('warehouseAddress.address')
                    ->label(__('purchase-request.warehouse_address.label'))
                    ->searchable()
                    ->wrapHeader()
                    ->placeholder('-')
                    ->color('gray')
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('created_at')
                    ->label(__('common.created_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                ,
                UserColumn::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->wrapHeader()
                    ->verticallyAlignStart()
                    ->wrap()
                    ->wrapped()
                ,
                TextColumn::make('status')
                    ->formatStateUsing(fn($state) => $state?->label())
                    ->icon(fn($state) => $state?->icon())
                    ->color(fn($state) => $state?->color())
                    ->badge()
                    ->grow(false)
                    ->sortable()
                    ->verticallyAlignStart()
                ,

                TextColumn::make('memo')
                    ->formatStateUsing(fn($state) => nl2br($state))
                    ->html()
                    ->searchable()
                    ->placeholder('-')
                    ->color('gray')
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('boq')
                    ->label(__('purchase-request.boq.label'))
                    ->formatStateUsing(fn($state) => nl2br($state))
                    ->html()
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
                TextColumn::make('purchase_orders_count')
                    ->counts('purchaseOrders')
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
                    ->sortable()
                    ->color('gray')
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('deleted_at')
                    ->label(__('common.deleted_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->placeholder('-')
                    ->color('gray')
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
