<?php

namespace App\Livewire;

use App\Filament\Resources\GoodsReceives\GoodsReceiveResource;
use App\Models\GoodsReceive;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;
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
                    ->label(__('goods-receive.number.label'))
                    ->description(fn ($record): HtmlString => new HtmlString(nl2br((string) $record->description)))
                    ->searchable()
                    ->sortable()
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->verticallyAlignStart()
                    ->wrap(),
                TextColumn::make('type')
                    ->label(__('goods-receive.type.label'))
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->icon(fn ($state) => $state?->icon())
                    ->badge()
                    ->color(fn ($state) => $state?->color())
                    ->sortable()
                    ->verticallyAlignStart(),
                TextColumn::make('created_at')
                    ->label(__('common.created_at.label'))
                    ->date()
                    ->sortable()
                    ->verticallyAlignStart(),
                UserColumn::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->wrap()
                    ->wrapped()
                    ->verticallyAlignStart(),
                TextColumn::make('status')
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->icon(fn ($state) => $state?->icon())
                    ->badge()
                    ->color(fn ($state) => $state?->color())
                    ->sortable()
                    ->verticallyAlignStart(),
                TextColumn::make('goods_receive_items_count')
                    ->label(__('goods-receive.goods_receive_items.label'))
                    ->sortable()
                    ->color('gray')
                    ->verticallyAlignStart(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->hiddenLabel()
                    ->url(fn ($record) => GoodsReceiveResource::getUrl('view', ['record' => $record->id])),
            ], position: RecordActionsPosition::BeforeColumns)
            ->defaultSort('id', 'asc')
            ->striped()
            ->stackedOnMobile()
            ->paginated(false);
    }
}

