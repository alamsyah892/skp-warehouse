<?php

namespace App\Livewire;

use App\Models\PurchaseOrderItem;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;

class PurchaseOrderItemsTable extends TableWidget
{
    public $record;

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query(
                PurchaseOrderItem::query()
                    ->with([
                        'item',
                        'purchaseRequestItem.purchaseRequest',
                        'goodsReceiveItems.goodsReceive',
                    ])
                    ->where('purchase_order_id', $this->record->id)
            )
            ->columns([
                TextColumn::make('sort')
                    ->label('#')
                    ->numeric()
                    ->color('gray')
                    ->alignEnd()
                    ->verticallyAlignStart()
                ,
                TextColumn::make('item.code')
                    ->label(__('item.code.label'))
                    ->wrapHeader()
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->fontFamily(FontFamily::Mono)
                    ->verticallyAlignStart()
                ,
                TextColumn::make('item.name')
                    ->label(__('item.name.label') . ' | ' . __('common.description.label'))
                    ->wrapHeader()
                    ->description(function ($record): HtmlString {
                        $descriptionLines = collect([
                            filled($record->description) ? nl2br($record->description) : null,
                            $record->purchaseRequestItem?->purchaseRequest?->number
                            ? '# ' . $record->purchaseRequestItem->purchaseRequest->number
                            : null,
                        ])->filter();

                        return new HtmlString($descriptionLines->isNotEmpty() ? $descriptionLines->implode('<br>') : '');
                    })
                    ->searchable()
                    ->verticallyAlignStart()
                    ->wrap()
                ,
                TextColumn::make('item.unit')
                    ->label(__('item.unit.label'))
                    ->wrapHeader()
                    ->color('gray')
                    ->verticallyAlignStart()
                ,
                TextColumn::make('qty')
                    ->numeric()
                    ->alignEnd()
                    ->verticallyAlignStart()
                ,
                TextColumn::make('price')
                    ->label(__('purchase-order.purchase_order_item.price.label'))
                    ->wrapHeader()
                    ->numeric()
                    ->alignEnd()
                    ->verticallyAlignStart()
                ,
                TextColumn::make('subtotal')
                    ->label(__('purchase-order.subtotal.label'))
                    ->wrapHeader()
                    ->state(fn(PurchaseOrderItem $record): float => $record->getSubtotalAmount())
                    ->numeric()
                    ->alignEnd()
                    ->verticallyAlignStart()
                ,
                TextColumn::make('received_qty')
                    ->label(__('purchase-order.purchase_order_item.received_qty.label'))
                    ->wrapHeader()
                    ->state(fn(PurchaseOrderItem $record): float|null => $record->getReceivedQty() > 0 ? $record->getReceivedQty() : null)
                    ->placeholder('-')
                    ->numeric()
                    ->color(fn(PurchaseOrderItem $record): string => $record->getReceivedQtyColor())
                    ->weight(FontWeight::Bold)
                    ->alignEnd()
                    ->verticallyAlignStart()
                    ->visible(fn() => $this->record && $this->record->goodsReceives()->exists())
                ,
            ])
            ->defaultSort('sort', 'asc')
            ->striped()
            ->stackedOnMobile(false)
            ->paginated(false)
        ;
    }
}
