<?php

namespace App\Livewire;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
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
                    ->label(__('purchase-order.number.label'))
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
                TextColumn::make('type')
                    ->label(__('purchase-order.type.label'))
                    ->wrapHeader()
                    ->formatStateUsing(fn($state) => $state?->label())
                    ->icon(fn($state) => $state?->icon())
                    ->color(fn($state) => $state?->color())
                    ->alignCenter()
                    ->badge()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
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

                TextColumn::make('vendor.name')
                    ->label(__('vendor.model.label'))
                    ->wrapHeader()
                    ->formatStateUsing(fn($state): ?string => Str::limit($state, 20))
                    ->tooltip(fn($state): string => $state)
                    ->searchable()
                    ->verticallyAlignStart()
                    ->wrap()
                ,

                UserColumn::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->verticallyAlignStart()
                    ->wrap(false)
                    ->wrapped(false)
                ,

                TextColumn::make('purchase_order_items_count')
                    ->label(__('purchase-order.purchase_order_items.count_label'))
                    ->wrapHeader()
                    ->color('gray')
                    ->alignEnd()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                // ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('goods_receives_count')
                    ->label(__('goods-receive.model.plural_label'))
                    ->wrapHeader()
                    ->color('gray')
                    ->alignEnd()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                // ->toggleable(isToggledHiddenByDefault: true)
                ,
            ])
            ->recordActions([
                ViewAction::make()
                    ->hiddenLabel()
                    ->url(
                        fn($record) => PurchaseOrderResource::getUrl('view', [
                            'record' => $record->id,
                        ])
                    )
                ,
            ], position: RecordActionsPosition::BeforeColumns)
            ->defaultSort('id', 'asc')
            ->striped()
            ->stackedOnMobile(false)
            ->paginated(false)
        ;
    }
}
