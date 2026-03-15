<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use App\Models\PurchaseRequest;
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
use Zvizvi\UserFields\Components\UserColumn;

class PurchaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label(__('purchase-request.number.label'))
                    ->description(fn($record): string => $record->description)
                    ->searchable()
                    ->sortable()
                    ->fontFamily(FontFamily::Mono)
                    ->size(TextSize::Large)
                    ->weight(FontWeight::Bold)
                    ->wrap()
                ,
                TextColumn::make('warehouse.name')
                    ->label(__('warehouse.model.label'))
                    ->wrap()
                ,
                TextColumn::make('company.alias')
                    ->label(__('purchase-request.company.label'))
                    ->wrapHeader()
                    ->wrap()
                ,
                TextColumn::make('division.name')
                    ->label(__('division.model.label'))
                    ->wrap()
                ,
                TextColumn::make('project.name')
                    ->label(__('project.model.label'))
                    ->wrap()
                ,
                TextColumn::make('warehouseAddress.address')
                    ->label(__('purchase-request.warehouse_address.label'))
                    ->searchable()
                    ->wrapHeader()
                    ->placeholder('-')
                    ->color('gray')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('created_at')
                    ->label(__('common.created_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->wrap()
                ,
                UserColumn::make('user')
                    ->wrap()
                    ->wrapped()
                ,
                TextColumn::make('status')
                    ->formatStateUsing(fn($state) => PurchaseRequest::getStatusLabel($state))
                    ->icon(fn($state) => PurchaseRequest::getStatusIcon($state))
                    ->badge()
                    ->color(fn($state) => PurchaseRequest::getStatusColor($state))
                    ->grow(false)
                    ->sortable()
                ,

                TextColumn::make('memo')
                    ->searchable()
                    ->placeholder('-')
                    ->color('gray')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('boq')
                    ->label(__('purchase-request.boq.label'))
                    ->searchable()
                    ->placeholder('-')
                    ->color('gray')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('purchase_request_items_count')
                    ->label(__('purchase-request.purchase_request_items.count_label'))
                    ->wrapHeader()
                    ->sortable()
                    ->color('gray')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,

                TextColumn::make('updated_at')
                    ->label(__('common.updated_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->color('gray')
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
