<?php

namespace App\Livewire;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Str;
use Zvizvi\UserFields\Components\UserColumn;

class PurchaseRequestPurchaseOrdersTable extends TableWidget
{
    public $record;

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query(
                PurchaseOrder::query()
                    ->withQuantitySummary()
                    ->with([
                        'vendor',
                    ])
                    ->withCount([
                        'purchaseOrderItems',
                        'goodsReceives',
                    ])
                    ->whereHas(
                        'purchaseRequests',
                        fn($query) => $query->whereKey($this->record->id)
                    )
            )
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

                TextColumn::make('vendor.name')
                    ->label(__('vendor.model.label'))
                    ->wrapHeader()
                    ->limit(16)
                    ->tooltip(fn($state): string => $state)
                    ->size(TextSize::ExtraSmall)
                    ->wrap(false)
                    ->toggleable(isToggledHiddenByDefault: false)
                ,

                TextColumn::make('purchase_order_items_count')
                    ->label(__('purchase-order.purchase_order_items.count_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->wrap(false)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                ,
                TextColumn::make('purchase_order_items_sum_qty')
                    ->label(__('purchase-order.purchase_order_items.sum_qty_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->color('gray')
                    ->alignEnd()
                    ->wrap(false)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('goods_receives_count')
                    ->label(__('purchase-order.goods_receives.count_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->wrap(false)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                ,
                TextColumn::make('purchase_order_items_received_qty_sum')
                    ->label(__('purchase-order.purchase_order_items.received_qty_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->color('gray')
                    ->alignEnd()
                    ->wrap(false)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('purchase_order_items_received_percentage')
                    ->label(__('purchase-order.purchase_order_items.received_percentage_label'))
                    ->wrapHeader()
                    ->formatStateUsing(fn($state): string => $state . '%')
                    ->color(fn($record): string => match (true) {
                        $record->getReceivedPercentage() <= 0 => 'danger',
                        $record->getReceivedPercentage() < 100 => 'warning',
                        default => 'success',
                    })
                    ->alignCenter()
                    ->badge()
                    ->wrap(false)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                ,
            ])
            ->recordActions([
                ViewAction::make()
                    ->hiddenLabel()
                    ->url(fn($record) => PurchaseOrderResource::getUrl('view', ['record' => $record->id]))
                ,
            ], position: RecordActionsPosition::BeforeColumns)
            ->defaultSort('id', 'asc')

            ->striped()
            ->stackedOnMobile(false)

            ->paginated(false)
        ;
    }
}