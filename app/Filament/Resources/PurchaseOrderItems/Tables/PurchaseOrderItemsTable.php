<?php

namespace App\Filament\Resources\PurchaseOrderItems\Tables;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrderItem;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Zvizvi\UserFields\Components\UserColumn;

class PurchaseOrderItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('item.name')
                    ->label(__('item.name.label'))
                    ->wrapHeader()
                    ->description(fn(PurchaseOrderItem $record): string => "SKU: {$record->item?->code} | Unit: {$record->item?->unit}")
                    ->tooltip(fn(PurchaseOrderItem $record): ?string => $record->description ?: $record->item?->name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('purchase_order_items.description', 'like', "%{$search}%")
                            ->orWhereHas('item', function (Builder $itemQuery) use ($search): void {
                                $itemQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            });
                    })
                    ->sortable()
                    ->wrap(false),
                TextColumn::make('qty')
                    ->label(__('purchase-order.purchase_order_items.sum_qty_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->sortable()
                    ->wrap(false),
                TextColumn::make('price')
                    ->label(__('purchase-order.purchase_order_item.price.label'))
                    ->wrapHeader()
                    ->numeric(decimalPlaces: 2)
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->sortable()
                    ->wrap(false),
                TextColumn::make('subtotal')
                    ->label(__('purchase-order.subtotal.label'))
                    ->wrapHeader()
                    ->state(fn(PurchaseOrderItem $record): float => $record->getSubtotalAmount())
                    ->numeric(decimalPlaces: 2)
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderByRaw('(purchase_order_items.qty * purchase_order_items.price) ' . $direction))
                    ->wrap(false),
                TextColumn::make('goods_receive_items_received_qty_sum')
                    ->label(__('purchase-order.purchase_order_items.received_qty_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->color('gray')
                    ->alignEnd()
                    ->sortable()
                    ->wrap(false),
                TextColumn::make('remaining_received_qty')
                    ->label(__('purchase-order.purchase_order_items.remaining_received_qty_label'))
                    ->wrapHeader()
                    ->state(fn(PurchaseOrderItem $record): float => $record->getRemainingReceivedQty())
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->sortable(query: fn(Builder $query, string $direction) => $query->orderByRaw('(purchase_order_items.qty - goods_receive_items_received_qty_sum) ' . $direction))
                    ->wrap(false),
                TextColumn::make('goods_receive_items_received_percentage')
                    ->label(__('purchase-order.purchase_order_items.received_percentage_label'))
                    ->wrapHeader()
                    ->formatStateUsing(fn($state): string => $state . '%')
                    ->color(fn(PurchaseOrderItem $record): string => match (true) {
                        $record->getReceivedPercentage() <= 0 => 'danger',
                        $record->getReceivedPercentage() < 100 => 'warning',
                        default => 'success',
                    })
                    ->badge()
                    ->alignCenter()
                    ->sortable()
                    ->wrap(false),
                TextColumn::make('purchaseOrder.number')
                    ->label(__('purchase-order.number.label'))
                    ->wrapHeader()
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->description(fn(PurchaseOrderItem $record): ?string => Str::limit($record->purchaseOrder?->description, 32))
                    ->tooltip(fn(PurchaseOrderItem $record): ?string => $record->purchaseOrder?->description)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('purchaseOrder', function (Builder $purchaseOrderQuery) use ($search): void {
                            $purchaseOrderQuery
                                ->where('number', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    })
                    ->sortable()
                    ->width('1%')
                    ->wrap(false),
                IconColumn::make('purchaseOrder.status')
                    ->label(__('purchase-order.purchase_order_items.purchase_order_status_label'))
                    ->wrapHeader()
                    ->icon(fn($state) => $state?->icon())
                    ->color(fn($state) => $state?->color())
                    ->tooltip(fn($state) => $state?->label())
                    ->alignCenter()
                    ->width('1%'),
                TextColumn::make('purchaseOrder.vendor.name')
                    ->label(__('vendor.model.label'))
                    ->wrapHeader()
                    ->searchable()
                    ->sortable()
                    ->wrap(false),
                TextColumn::make('purchaseOrder.created_at')
                    ->label(__('common.created_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->size(TextSize::ExtraSmall)
                    ->sortable()
                    ->wrap(false),
                UserColumn::make('purchaseOrder.user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->tooltip(fn($state) => $state?->name)
                    ->size(TextSize::ExtraSmall),
                TextColumn::make('purchaseOrder.project.name')
                    ->label(__('project.warehouse_project.label'))
                    ->wrapHeader()
                    ->limit(16)
                    ->description(
                        fn(PurchaseOrderItem $record): ?string =>
                        Str::limit(
                            "{$record->purchaseOrder?->warehouse?->name} - {$record->purchaseOrder?->company?->alias} - {$record->purchaseOrder?->division?->name}",
                            16
                        )
                    )
                    ->tooltip(
                        fn(PurchaseOrderItem $record): ?string =>
                        "{$record->purchaseOrder?->warehouse?->name} - {$record->purchaseOrder?->company?->alias} - {$record->purchaseOrder?->division?->name} - {$record->purchaseOrder?->project?->name}"
                    )
                    ->size(TextSize::ExtraSmall)
                    ->sortable()
                    ->wrap(false),
            ])
            ->filters([
                SelectFilter::make('vendor')
                    ->label(__('vendor.model.plural_label'))
                    ->relationship(
                        'purchaseOrder.vendor',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code')
                    )
                    ->multiple()
                    ->searchable(['code', 'name'])
                    ->preload(),
                SelectFilter::make('warehouse')
                    ->label(__('warehouse.model.plural_label'))
                    ->relationship(
                        'purchaseOrder.warehouse',
                        'name',
                        fn($query) => $query->when(
                            auth()->user()->warehouses()->exists(),
                            fn($warehouseQuery) => $warehouseQuery->whereIn('warehouses.id', auth()->user()->warehouses->pluck('id'))
                        )->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable(['code', 'name'])
                    ->preload(),
                SelectFilter::make('company')
                    ->label(__('company.warehouse.label'))
                    ->relationship(
                        'purchaseOrder.company',
                        'alias',
                        fn($query) => $query->orderBy('alias')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable(['code', 'name', 'alias'])
                    ->preload(),
                SelectFilter::make('division')
                    ->label(__('division.model.plural_label'))
                    ->relationship(
                        'purchaseOrder.division',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable(['code', 'name'])
                    ->preload(),
                SelectFilter::make('project')
                    ->label(__('project.model.plural_label'))
                    ->relationship(
                        'purchaseOrder.project',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} / {$record->po_code} | {$record->name}")
                    ->multiple()
                    ->searchable(['code', 'po_code', 'name'])
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->hiddenLabel()
                    ->url(fn(PurchaseOrderItem $record): string => PurchaseOrderResource::getUrl('view', [
                        'record' => $record->purchase_order_id,
                    ])),
            ], position: RecordActionsPosition::BeforeColumns)
            ->striped()
            ->stackedOnMobile()
            ->contentGrid([])
            ->paginated([5, 10, 25, 50, 100])
            ->paginationMode(PaginationMode::Default)
            ->defaultPaginationPageOption(10);
    }
}
