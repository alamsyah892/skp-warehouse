<?php

namespace App\Livewire;

use App\Filament\Resources\GoodsReceives\GoodsReceiveResource;
use App\Models\GoodsReceive;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Str;
use Zvizvi\UserFields\Components\UserColumn;

class PurchaseOrderGoodsReceivesTable extends TableWidget
{
    public $record;

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query(
                GoodsReceive::query()
                    ->withQuantitySummary()
                    ->with([
                        'user',
                    ])
                    ->withCount([
                        'goodsReceiveItems',
                    ])
                    ->where('purchase_order_id', $this->record->id),
            )
            ->columns([
                TextColumn::make('number')
                    ->label(__('common.number.label'))
                    ->wrapHeader()
                    ->description(fn($record): ?string => Str::limit($record->description, 32))
                    ->tooltip(fn($record): string => $record->description)
                    ->searchable(['number', 'description'])
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    // ->size(TextSize::Large)
                    ->fontFamily(FontFamily::Mono)
                    ->verticallyAlignStart()
                    ->wrap(false)
                    ->grow(false)
                ,

                TextColumn::make('type')
                    ->label(__('goods-receive.type.label'))
                    ->wrapHeader()
                    ->formatStateUsing(fn($state) => '')
                    ->icon(fn($state) => $state?->icon())
                    ->color(fn($state) => $state?->color())
                    ->tooltip(fn($state) => $state?->label())
                    ->size(TextSize::Large)
                    ->alignCenter()
                    ->badge()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                ,

                TextColumn::make('status')
                    ->formatStateUsing(fn($state) => '')
                    ->icon(fn($state) => $state?->icon())
                    ->color(fn($state) => $state?->color())
                    ->tooltip(fn($state) => $state?->label())
                    ->size(TextSize::Large)
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

                UserColumn::make('user')
                    ->label((__('common.log_activity.created.label') . ' ' . __('common.log_activity.by')))
                    ->wrapHeader()
                    ->verticallyAlignStart()
                    ->wrap(false)
                    ->wrapped(false)
                    ->grow(false)
                    ->toggleable(isToggledHiddenByDefault: false)
                ,

                TextColumn::make('goods_receive_items_count')
                    ->label(__('goods-receive.goods_receive_items.count_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->color('gray')
                    ->alignEnd()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false)
                ,
                TextColumn::make('goods_receive_items_sum_qty')
                    ->label(__('goods-receive.goods_receive_items.sum_qty_label'))
                    ->wrapHeader()
                    ->formatStateUsing(fn($state): string => $state)
                    ->numeric()
                    ->color('gray')
                    ->alignEnd()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
            ])
            ->recordActions([
                ViewAction::make()
                    ->hiddenLabel()
                    ->url(fn($record) => GoodsReceiveResource::getUrl('view', ['record' => $record->id]))
                ,
            ], position: RecordActionsPosition::BeforeColumns)
            ->defaultSort('id', 'asc')
            ->striped()
            ->stackedOnMobile()
            ->paginated(false);
    }
}

