<?php

namespace App\Filament\Resources\GoodsReceives\Tables;

use App\Enums\GoodsReceiveType;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
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

class GoodsReceivesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label(__('common.number.label'))
                    ->wrapHeader()
                    ->icon(fn($record) => $record->type->icon())
                    ->iconColor(fn($record) => $record->type->color())
                    ->iconPosition(IconPosition::After)
                    ->weight(FontWeight::Bold)
                    ->fontFamily(FontFamily::Mono)
                    ->description(fn($record): ?string => Str::limit($record->description, 32))
                    ->tooltip(fn($record): ?string => $record->description)
                    ->wrap(false)
                    ->searchable(['number', 'description'])
                    ->sortable()
                    ->width('1%')
                ,
                IconColumn::make('status')
                    ->icon(fn($state) => $state->icon())
                    ->color(fn($state) => $state->color())
                    ->tooltip(fn($state) => $state->label())
                    ->alignCenter()
                    ->width('1%')
                ,
                TextColumn::make('created_at')
                    ->label(__('common.created_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->size(TextSize::ExtraSmall)
                    ->wrap(false)
                    ->sortable()
                ,

                UserColumn::make('user')
                    ->label((__('common.log_activity.created.label') . ' ' . __('common.log_activity.by')))
                    ->tooltip(fn($state) => $state?->name)
                    ->size(TextSize::ExtraSmall)
                    ->toggleable(isToggledHiddenByDefault: false)
                ,

                TextColumn::make('project.name')
                    ->label(__('project.warehouse_project.label'))
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
                    ->label(__('warehouse-address.receiving.label'))
                    ->wrapHeader()
                    ->limit(16)
                    ->tooltip(fn($state) => $state)
                    ->placeholder('-')
                    ->size(TextSize::ExtraSmall)
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true)
                ,

                TextColumn::make('purchaseOrder.number')
                    ->label(__('purchase-order.model.plural_label'))
                    ->wrapHeader()
                    ->fontFamily(FontFamily::Mono)
                    ->listWithLineBreaks()
                    ->badge()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                ,

                TextColumn::make('goods_receive_items_count')
                    ->label(__('goods-receive.goods_receive_items.count_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->wrap(false)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                ,
                TextColumn::make('goods_receive_items_sum_qty')
                    ->label(__('goods-receive.goods_receive_items.sum_qty_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->color('gray')
                    ->alignEnd()
                    ->wrap(false)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,

                TextColumn::make('updated_at')
                    ->label(__('common.updated_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->size(TextSize::ExtraSmall)
                    ->color('gray')
                    ->wrap(false)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('deleted_at')
                    ->label(__('common.deleted_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->placeholder('-')
                    ->size(TextSize::ExtraSmall)
                    ->color('gray')
                    ->wrap(false)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('goods-receive.type.label'))
                    ->options(GoodsReceiveType::options())
                    ->native(false)
                    ->multiple()
                    ->preload()
                ,

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
                    ->label(__('company.warehouse.label'))
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
                    ->searchable(['code', 'po_code', 'name'])
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
