<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

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

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label(__('purchase-order.number.label'))
                    ->description(fn ($record): string => $record->description)
                    ->searchable()
                    ->sortable()
                    ->fontFamily(FontFamily::Mono)
                    ->size(TextSize::Large)
                    ->weight(FontWeight::Bold)
                    ->wrap(),
                TextColumn::make('vendor.name')
                    ->label(__('vendor.model.label'))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('warehouse.name')->label(__('warehouse.model.label'))->wrap(),
                TextColumn::make('company.alias')->label(__('purchase-order.company.label'))->wrap(),
                TextColumn::make('division.name')->label(__('division.model.label'))->wrap(),
                TextColumn::make('project.name')->label(__('project.model.label'))->wrap(),
                TextColumn::make('created_at')->label(__('common.created_at.label'))->date()->sortable()->wrap(),
                UserColumn::make('user')->wrap()->wrapped(),
                TextColumn::make('status')
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->icon(fn ($state) => $state?->icon())
                    ->badge()
                    ->color(fn ($state) => $state?->color())
                    ->grow(false)
                    ->sortable(),
                TextColumn::make('purchase_order_items_count')
                    ->label(__('purchase-order.purchase_order_items.count_label'))
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('common.updated_at.label'))
                    ->date()
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('common.deleted_at.label'))
                    ->date()
                    ->sortable()
                    ->placeholder('-')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('vendor')
                    ->label(__('vendor.model.label'))
                    ->relationship('vendor', 'name', fn ($query) => $query->orderBy('name')->orderBy('code'))
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('warehouse')
                    ->label(__('warehouse.model.label'))
                    ->relationship(
                        'warehouse',
                        'name',
                        fn ($query) => $query
                            ->when(
                                auth()->user()->warehouses()->exists(),
                                fn ($q) => $q->whereIn('warehouses.id', auth()->user()->warehouses->pluck('id'))
                            )
                            ->orderBy('name')->orderBy('code')
                    )
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('company')
                    ->label(__('purchase-order.company.label'))
                    ->relationship('company', 'alias', fn ($query) => $query->orderBy('alias')->orderBy('code'))
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('division')
                    ->label(__('division.model.label'))
                    ->relationship('division', 'name', fn ($query) => $query->orderBy('name')->orderBy('code'))
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('project')
                    ->label(__('project.model.label'))
                    ->relationship('project', 'name', fn ($query) => $query->orderBy('name')->orderBy('code'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} | {$record->name}")
                    ->searchable(['code', 'name'])
                    ->multiple()
                    ->preload(),
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
            ->defaultPaginationPageOption(10);
    }
}
