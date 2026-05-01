<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\IconColumn;
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
                    ->label(__('common.number.label'))
                    ->wrapHeader()
                    ->description(fn($record): ?string => Str::limit($record->description, 32))
                    ->tooltip(fn($record): string => $record->description)
                    ->searchable(['number', 'description'])
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->fontFamily(FontFamily::Mono)
                    ->wrap(false)
                    ->width('1%')
                ,
                IconColumn::make('status')
                    ->icon(fn($state) => $state->icon())
                    ->color(fn($state) => $state->color())
                    ->tooltip(fn($state) => $state->label())
                    // ->size(IconSize::Small)
                    ->alignCenter()
                    ->width('1%')
                ,
                TextColumn::make('created_at')
                    ->label(__('common.created_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->size(TextSize::ExtraSmall)
                    ->wrap(false)
                ,

                UserColumn::make('user')
                    ->label((__('common.log_activity.created.label') . ' ' . __('common.log_activity.by')))
                    ->size(TextSize::ExtraSmall)
                    ->toggleable(isToggledHiddenByDefault: false)
                ,

                TextColumn::make('project.name')
                    ->label(__('purchase-request.fieldset.warehouse_project.label'))
                    ->wrapHeader()
                    ->limit(16)
                    ->description(
                        fn($record): ?string =>
                        Str::limit($record->warehouse->name . ' - ' . $record->company->alias . ' - ' . $record->division->name, 16)
                    )
                    ->tooltip(
                        fn($record): ?string =>
                        $record->warehouse->name . ' - ' . $record->company->alias . ' - ' . $record->division->name . ' - ' . $record->project->name
                    )
                    ->size(TextSize::ExtraSmall)
                    ->wrap(false)
                    ->toggleable(isToggledHiddenByDefault: false)
                ,

                TextColumn::make('warehouseAddress.address')
                    ->label(__('purchase-request.warehouse_address.label'))
                    ->wrapHeader()
                    ->limit(16)
                    ->tooltip(fn($state) => $state)
                    ->placeholder('-')
                    ->color('gray')
                    ->size(TextSize::ExtraSmall)
                    ->toggleable(isToggledHiddenByDefault: true)
                ,

                TextColumn::make('memo')
                    ->limit(16)
                    ->tooltip(fn($state) => $state)
                    ->searchable()
                    ->placeholder('-')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('boq')
                    ->label(__('purchase-request.boq.label'))
                    ->wrapHeader()
                    ->limit(16)
                    ->tooltip(fn($state) => $state)
                    ->searchable()
                    ->placeholder('-')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true)
                ,

                TextColumn::make('purchase_request_items_count')
                    ->label(__('purchase-request.purchase_request_items.count_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->color('gray')
                    ->alignEnd()
                    ->sortable()
                    ->wrap(false)
                    ->toggleable(isToggledHiddenByDefault: false)
                ,
                TextColumn::make('purchase_request_items_sum_qty')
                    ->label(__('purchase-request.purchase_request_items.sum_qty_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->color('gray')
                    ->alignEnd()
                    ->sortable()
                    ->size(TextSize::ExtraSmall)
                    ->wrap(false)
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('purchase_orders_count')
                    ->label(__('purchase-request.purchase_orders.count_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->color('gray')
                    ->alignEnd()
                    ->sortable()
                    ->size(TextSize::ExtraSmall)
                    ->wrap(false)
                    ->toggleable(isToggledHiddenByDefault: false)
                ,
                TextColumn::make('purchase_request_items_ordered_qty_sum')
                    ->label(__('purchase-request.purchase_request_items.ordered_qty_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->color('gray')
                    ->alignEnd()
                    ->sortable()
                    ->size(TextSize::ExtraSmall)
                    ->wrap(false)
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('purchase_request_items_ordered_percentage')
                    ->label(__('purchase-request.purchase_request_items.ordered_percentage_label'))
                    ->wrapHeader()
                    ->formatStateUsing(fn($state): string => $state . '%')
                    ->color(fn($record): string => match (true) {
                        $record->getOrderedPercentage() <= 0.0 => 'danger',
                        $record->getOrderedPercentage() < 100.0 => 'warning',
                        default => 'success',
                    })
                    ->alignCenter()
                    ->badge()
                    ->sortable()
                    ->wrap(false)
                    ->toggleable(isToggledHiddenByDefault: false)
                ,

                TextColumn::make('updated_at')
                    ->label(__('common.updated_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->color('gray')
                    ->sortable()
                    ->size(TextSize::ExtraSmall)
                    ->wrap(false)
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('deleted_at')
                    ->label(__('common.deleted_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->color('gray')
                    ->placeholder('-')
                    ->sortable()
                    ->size(TextSize::ExtraSmall)
                    ->wrap(false)
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
            ])
            ->filters([
                SelectFilter::make('warehouse')
                    ->label(__('warehouse.model.plural_label'))
                    ->relationship(
                        'warehouse',
                        'name',
                        fn($query) => $query->when(
                            auth()->user()->warehouses()->exists(),
                            fn($q) => $q->whereIn('warehouses.id', auth()->user()->warehouses->pluck('id'))
                        )->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable(['code', 'name'])
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
                    ->searchable(['code', 'name', 'alias'])
                    ->preload()
                ,
                SelectFilter::make('division')
                    ->label(__('division.model.plural_label'))
                    ->relationship(
                        'division',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable(['code', 'name'])
                    ->preload()
                ,
                SelectFilter::make('project')
                    ->label(__('project.model.plural_label'))
                    ->relationship(
                        'project',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} / {$record->po_code} | {$record->name}")
                    ->multiple()
                    ->searchable(['name', 'code', 'po_code'])
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
