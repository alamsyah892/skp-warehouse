<?php

namespace App\Livewire;

use App\Filament\Resources\GoodsReceives\GoodsReceiveResource;
use App\Models\GoodsReceive;
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
                    ->whereHas(
                        'purchaseOrder',
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
            ])
            ->recordActions([
                ViewAction::make()
                    ->hiddenLabel()
                    ->url(fn($record) => GoodsReceiveResource::getUrl('view', ['record' => $record->id]))
                ,
            ], position: RecordActionsPosition::BeforeColumns)
            ->defaultSort('id', 'asc')

            ->striped()
            ->stackedOnMobile(false)

            ->paginated(false)
        ;
    }
}