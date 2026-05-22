<?php

namespace App\Filament\Resources\PurchaseRequestItems\Tables;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequestItem;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Zvizvi\UserFields\Components\UserColumn;

class PurchaseRequestItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColumnGroup::make('Item', [
                    TextColumn::make('item.name')
                        ->label(__('item.name.label'))
                        ->wrapHeader()
                        ->description(fn(PurchaseRequestItem $record): string => "SKU: {$record->item?->code} | Unit: {$record->item?->unit}")
                        ->tooltip(fn(PurchaseRequestItem $record): ?string => $record->description ?: $record->item?->name)
                        ->size(TextSize::ExtraSmall)
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query
                                ->where('purchase_request_items.description', 'like', "%{$search}%")
                                ->orWhereHas('item', function (Builder $itemQuery) use ($search): void {
                                    $itemQuery
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('code', 'like', "%{$search}%");
                                });
                        })
                        ->sortable()
                        ->wrap(false)
                        ->toggleable(isToggledHiddenByDefault: false)
                    ,
                    TextColumn::make('qty')
                        ->numeric()
                        ->alignEnd()
                        ->sortable()
                        ->wrap(false)
                        ->summarize(Sum::make()->hiddenLabel())
                    ,
                    TextColumn::make('purchase_order_items_ordered_qty_sum')
                        ->label(__('purchase-request.purchase_request_items.ordered_qty_label'))
                        ->wrapHeader()
                        ->numeric()
                        ->color('gray')
                        ->alignEnd()
                        ->sortable()
                        ->wrap(false)
                        ->summarize(Sum::make()->hiddenLabel())
                    ,
                    // TextColumn::make('remaining_ordered_qty')
                    //     ->label(__('purchase-request.purchase_request_items.remaining_ordered_qty_label'))
                    //     ->wrapHeader()
                    //     ->state(fn(PurchaseRequestItem $record): float => $record->getRemainingOrderedQty())
                    //     ->numeric()
                    //     ->alignEnd()
                    //     ->sortable(query: fn(Builder $query, string $direction) => $query->orderByRaw("(purchase_request_items.qty - purchase_order_items_ordered_qty_sum) {$direction}"))
                    //     ->wrap(false)
                    //     ->toggleable(isToggledHiddenByDefault: false)
                    // ,
                    TextColumn::make('purchase_order_items_ordered_percentage')
                        ->label(__('purchase-request.purchase_request_items.ordered_percentage_label'))
                        ->wrapHeader()
                        ->formatStateUsing(fn($state): string => $state . '%')
                        ->color(fn(PurchaseRequestItem $record): string => match (true) {
                            $record->getOrderedPercentage() <= 0 => 'danger',
                            $record->getOrderedPercentage() < 100 => 'warning',
                            default => 'success',
                        })
                        ->badge()
                        ->alignCenter()
                        ->sortable()
                        ->wrap(false)
                        ->summarize(Average::make()->hiddenLabel())
                    ,
                    TextColumn::make('purchase_order_items_received_qty_sum')
                        ->label(__('purchase-request.purchase_request_items.received_qty_label'))
                        ->wrapHeader()
                        ->numeric()
                        ->color('gray')
                        ->alignEnd()
                        ->sortable()
                        ->wrap(false)
                        ->summarize(Sum::make()->hiddenLabel())
                    ,
                    // TextColumn::make('remaining_received_qty')
                    //     ->label(__('purchase-request.purchase_request_items.remaining_received_qty_label'))
                    //     ->wrapHeader()
                    //     ->state(fn(PurchaseRequestItem $record): float => $record->getRemainingReceivedQty())
                    //     ->numeric()
                    //     ->alignEnd()
                    //     ->sortable(query: fn(Builder $query, string $direction) => $query->orderByRaw("(purchase_request_items.qty - purchase_order_items_received_qty_sum) {$direction}"))
                    //     ->wrap(false)
                    //     ->toggleable(isToggledHiddenByDefault: false)
                    // ,
                    TextColumn::make('purchase_order_items_received_percentage')
                        ->label(__('purchase-request.purchase_request_items.received_percentage_label'))
                        ->wrapHeader()
                        ->formatStateUsing(fn($state): string => $state . '%')
                        ->color(fn(PurchaseRequestItem $record): string => match (true) {
                            $record->getReceivedPercentage() <= 0 => 'danger',
                            $record->getReceivedPercentage() < 100 => 'warning',
                            default => 'success',
                        })
                        ->badge()
                        ->alignCenter()
                        ->sortable()
                        ->wrap(false)
                        ->summarize(Average::make()->hiddenLabel())
                    ,
                ]),

                ColumnGroup::make(__('purchase-request.model.plural_label'), [
                    TextColumn::make('purchaseRequest.number')
                        ->label(__('purchase-request.number.label'))
                        ->wrapHeader()
                        ->fontFamily(FontFamily::Mono)
                        ->weight(FontWeight::Bold)
                        ->description(fn(PurchaseRequestItem $record): ?string => Str::limit($record->purchaseRequest?->description, 32))
                        ->tooltip(fn(PurchaseRequestItem $record): ?string => $record->purchaseRequest?->description)
                        ->searchable(query: function (Builder $query, string $search): Builder {
                            return $query->whereHas('purchaseRequest', function (Builder $purchaseRequestQuery) use ($search): void {
                                $purchaseRequestQuery
                                    ->where('number', 'like', "%{$search}%")
                                    ->orWhere('description', 'like', "%{$search}%");
                            });
                        })
                        ->sortable()
                        ->width('1%')
                        ->wrap(false)
                        ->toggleable(isToggledHiddenByDefault: false)
                    ,
                    IconColumn::make('purchaseRequest.status')
                        ->label('Status')
                        ->wrapHeader()
                        ->icon(fn($state) => $state?->icon())
                        ->color(fn($state) => $state?->color())
                        ->tooltip(fn($state) => $state?->label())
                        ->alignCenter()
                        ->width('1%')
                        ->toggleable(isToggledHiddenByDefault: false)
                    ,
                    TextColumn::make('purchaseRequest.created_at')
                        ->label(__('common.created_at.label'))
                        ->wrapHeader()
                        ->date()
                        ->size(TextSize::ExtraSmall)
                        ->sortable()
                        ->wrap(false)
                        ->toggleable(isToggledHiddenByDefault: false)
                    ,
                    UserColumn::make('purchaseRequest.user')
                        ->label((__('common.log_activity.created.label') . ' ' . __('common.log_activity.by')))
                        ->tooltip(fn($state) => $state?->name)
                        ->size(TextSize::ExtraSmall)
                        ->toggleable(isToggledHiddenByDefault: true)
                    ,
                    TextColumn::make('purchaseRequest.project.name')
                        ->label(__('project.warehouse_project.label'))
                        ->wrapHeader()
                        ->limit(16)
                        ->description(
                            fn(PurchaseRequestItem $record): ?string =>
                            Str::limit(
                                "{$record->purchaseRequest?->warehouse?->name} - {$record->purchaseRequest?->company?->alias} - {$record->purchaseRequest?->division?->name}",
                                16,
                            )
                        )
                        ->tooltip(
                            fn(PurchaseRequestItem $record): ?string =>
                            "{$record->purchaseRequest?->warehouse?->name} - {$record->purchaseRequest?->company?->alias} - {$record->purchaseRequest?->division?->name} - {$record->purchaseRequest?->project?->name}"
                        )
                        ->size(TextSize::ExtraSmall)
                        ->sortable()
                        ->wrap(false)
                        ->toggleable(isToggledHiddenByDefault: false)
                    ,
                ]),
            ])
            ->filters([
                SelectFilter::make('item')
                    ->label(__('item.model.plural_label'))
                    ->relationship(
                        'item',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                    ->multiple()
                    ->searchable(['code', 'name'])
                    ->preload()
                ,
                SelectFilter::make('warehouse')
                    ->label(__('warehouse.model.plural_label'))
                    ->relationship(
                        'purchaseRequest.warehouse',
                        'name',
                        fn($query) => $query->when(
                            auth()->user()->warehouses()->exists(),
                            fn($warehouseQuery) => $warehouseQuery->whereIn('warehouses.id', auth()->user()->warehouses->pluck('id'))
                        )->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable(['code', 'name'])
                    ->preload()
                ,
                SelectFilter::make('company')
                    ->label(__('company.warehouse.label'))
                    ->relationship(
                        'purchaseRequest.company',
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
                        'purchaseRequest.division',
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
                        'purchaseRequest.project',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} / {$record->po_code} | {$record->name}")
                    ->multiple()
                    ->searchable(['code', 'po_code', 'name'])
                    ->preload()
                ,
                Filter::make('not_fully_received')
                    ->label(__('purchase-request.purchase_request_items.not_fully_received_label'))
                    ->query(fn(Builder $query): Builder => $query->whereRaw(
                        'coalesce((
                            select sum(goods_receive_items.qty)
                            from goods_receive_items
                            inner join goods_receives
                                on goods_receives.id = goods_receive_items.goods_receive_id
                            inner join purchase_order_items
                                on purchase_order_items.id = goods_receive_items.purchase_order_item_id
                            inner join purchase_orders
                                on purchase_orders.id = purchase_order_items.purchase_order_id
                            where purchase_order_items.purchase_request_item_id = purchase_request_items.id
                                and purchase_orders.status != ?
                                and goods_receives.status in (?, ?)
                        ), 0) < purchase_request_items.qty',
                        [
                            \App\Enums\PurchaseOrderStatus::CANCELED->value,
                            \App\Enums\GoodsReceiveStatus::RECEIVED->value,
                            \App\Enums\GoodsReceiveStatus::CONFIRMED->value,
                        ]
                    ))
                ,
            ])
            ->groups([
                Group::make('item.name')
                    ->label('Item')
                ,
                Group::make('purchaseRequest.number')
                    ->label(__('purchase-request.model.plural_label'))
                    ->getDescriptionFromRecordUsing(fn(PurchaseRequestItem $record): ?string => Str::limit($record->purchaseRequest?->description, 32))
                    ->collapsible()
                ,
                // Group::make('purchaseRequest.created_at')
                //     ->label(__('common.created_at.label'))
                // ,
            ])
            ->summaries(
                pageCondition: false,
                allTableCondition: false
            )
            ->recordActions([
                ViewAction::make()
                    ->hiddenLabel()
                    ->url(fn(PurchaseRequestItem $record): string => PurchaseRequestResource::getUrl('view', [
                        'record' => $record->purchase_request_id,
                    ]))
                ,
            ], position: RecordActionsPosition::BeforeColumns)
            ->striped(false)
            ->stackedOnMobile()
            ->contentGrid([])
            ->paginated([5, 10, 25, 50, 100])
            ->paginationMode(PaginationMode::Default)
            ->defaultPaginationPageOption(10)
        ;
    }
}
