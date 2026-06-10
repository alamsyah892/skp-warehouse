<?php

namespace App\Filament\Resources\GoodsReceiveItems\Tables;

use App\Filament\Resources\GoodsReceives\GoodsReceiveResource;
use App\Models\GoodsReceiveItem;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Zvizvi\UserFields\Components\UserColumn;

class GoodsReceiveItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColumnGroup::make('Item', [
                    TextColumn::make('item.name')
                        ->label(__('item.name.label'))
                        ->wrapHeader()
                        ->description(fn (GoodsReceiveItem $record): string => "SKU: {$record->item?->code} | Unit: {$record->item?->unit}")
                        ->tooltip(fn (GoodsReceiveItem $record): ?string => $record->description ?: $record->item?->name)
                        ->size(TextSize::ExtraSmall)
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query
                                ->where('goods_receive_items.description', 'like', "%{$search}%")
                                ->orWhereHas('item', function (Builder $itemQuery) use ($search): void {
                                    $itemQuery
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('code', 'like', "%{$search}%");
                                });
                        })
                        ->sortable()
                        ->wrap(false),
                    TextColumn::make('qty')
                        ->wrapHeader()
                        ->numeric()
                        ->alignEnd()
                        ->sortable()
                        ->wrap(false)
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->summarize(Sum::make()),
                    TextColumn::make('sort')
                        ->label('#')
                        ->numeric()
                        ->size(TextSize::ExtraSmall)
                        ->color('gray')
                        ->alignEnd()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),

                ColumnGroup::make(__('goods-receive.model.label'), [
                    TextColumn::make('goodsReceive.number')
                        ->label(__('common.number.label'))
                        ->wrapHeader()
                        ->fontFamily(FontFamily::Mono)
                        ->weight(FontWeight::Bold)
                        ->description(fn (GoodsReceiveItem $record): ?string => Str::limit($record->goodsReceive?->description, 32))
                        ->tooltip(fn (GoodsReceiveItem $record): ?string => $record->goodsReceive?->description)
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->whereHas('goodsReceive', function (Builder $goodsReceiveQuery) use ($search): void {
                                $goodsReceiveQuery
                                    ->where('number', 'like', "%{$search}%")
                                    ->orWhere('description', 'like', "%{$search}%");
                            });
                        })
                        ->sortable()
                        ->width('1%')
                        ->wrap(false)
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->summarize(Count::make()),
                    IconColumn::make('goodsReceive.status')
                        ->label('Status')
                        ->wrapHeader()
                        ->icon(fn ($state) => $state?->icon())
                        ->color(fn ($state) => $state?->color())
                        ->tooltip(fn ($state) => $state?->label())
                        ->alignCenter()
                        ->width('1%')
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('goodsReceive.type')
                        ->label(__('goods-receive.type.label'))
                        ->wrapHeader()
                        ->formatStateUsing(fn ($state): ?string => $state?->label())
                        ->badge()
                        ->color(fn ($state): ?string => $state?->color())
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('goodsReceive.purchaseOrder.number')
                        ->label(__('purchase-order.model.label'))
                        ->wrapHeader()
                        ->fontFamily(FontFamily::Mono)
                        ->badge()
                        ->searchable()
                        ->toggleable(isToggledHiddenByDefault: false),
                    TextColumn::make('goodsReceive.created_at')
                        ->label(__('common.created_at.label'))
                        ->wrapHeader()
                        ->date()
                        ->size(TextSize::ExtraSmall)
                        ->sortable()
                        ->wrap(false),
                    UserColumn::make('goodsReceive.user')
                        ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                        ->tooltip(fn ($state) => $state?->name)
                        ->size(TextSize::ExtraSmall)
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('goodsReceive.project.name')
                        ->label(__('project.warehouse_project.label'))
                        ->wrapHeader()
                        ->limit(16)
                        ->description(
                            fn (GoodsReceiveItem $record): ?string => Str::limit(
                                "{$record->goodsReceive?->warehouse?->name} - {$record->goodsReceive?->company?->alias} - {$record->goodsReceive?->division?->name}",
                                16
                            )
                        )
                        ->tooltip(
                            fn (GoodsReceiveItem $record): ?string => "{$record->goodsReceive?->warehouse?->name} - {$record->goodsReceive?->company?->alias} - {$record->goodsReceive?->division?->name} - {$record->goodsReceive?->project?->name}"
                        )
                        ->size(TextSize::ExtraSmall)
                        ->sortable()
                        ->wrap(false)
                        ->toggleable(isToggledHiddenByDefault: false),
                ]),
            ])
            ->filters([
                SelectFilter::make('item')
                    ->label(__('item.model.plural_label'))
                    ->relationship(
                        'item',
                        'name',
                        fn ($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} | {$record->name}")
                    ->multiple()
                    ->searchable(['code', 'name'])
                    ->preload(),
                SelectFilter::make('warehouse')
                    ->label(__('warehouse.model.plural_label'))
                    ->relationship(
                        'goodsReceive.warehouse',
                        'name',
                        fn ($query) => $query->when(
                            auth()->user()->warehouses()->exists(),
                            fn ($warehouseQuery) => $warehouseQuery->whereIn('warehouses.id', auth()->user()->warehouses->pluck('id'))
                        )->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable(['code', 'name'])
                    ->preload(),
                SelectFilter::make('company')
                    ->label(__('company.warehouse.label'))
                    ->relationship(
                        'goodsReceive.company',
                        'alias',
                        fn ($query) => $query->orderBy('alias')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable(['code', 'name', 'alias'])
                    ->preload(),
                SelectFilter::make('division')
                    ->label(__('division.model.plural_label'))
                    ->relationship(
                        'goodsReceive.division',
                        'name',
                        fn ($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable(['code', 'name'])
                    ->preload(),
                SelectFilter::make('project')
                    ->label(__('project.model.plural_label'))
                    ->relationship(
                        'goodsReceive.project',
                        'name',
                        fn ($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} / {$record->po_code} | {$record->name}")
                    ->multiple()
                    ->searchable(['code', 'po_code', 'name'])
                    ->preload(),
            ])
            ->groups([
                Group::make('item.name')
                    ->label('Item'),
                Group::make('goodsReceive.number')
                    ->label(__('goods-receive.model.plural_label'))
                    ->getDescriptionFromRecordUsing(fn (GoodsReceiveItem $record): ?string => Str::limit($record->goodsReceive?->description, 32))
                    ->collapsible(),
            ])
            ->summaries(
                pageCondition: false,
                allTableCondition: false
            )
            ->recordActions([
                ViewAction::make()
                    ->hiddenLabel()
                    ->url(fn (GoodsReceiveItem $record): string => GoodsReceiveResource::getUrl('view', [
                        'record' => $record->goods_receive_id,
                    ])),
            ], position: RecordActionsPosition::BeforeColumns)
            ->striped(false)
            ->stackedOnMobile()
            ->contentGrid([])
            ->paginated([5, 10, 25, 50, 100])
            ->paginationMode(PaginationMode::Default)
            ->defaultPaginationPageOption(10);
    }
}
