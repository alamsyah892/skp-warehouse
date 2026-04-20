<?php

namespace App\Livewire;

use App\Models\PurchaseOrderItem;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;

class PurchaseOrderItemsTable extends TableWidget
{
    public $record;

    public static function getReceivedQtyColumnColor(PurchaseOrderItem $purchaseOrderItem): string
    {
        $receivedQty = $purchaseOrderItem->getReceivedQty();
        $requestedQty = (float) $purchaseOrderItem->qty;

        return match (true) {
            $receivedQty == 0 => 'danger',
            $receivedQty < $requestedQty => 'info',
            default => 'success',
        };
    }

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
                    ->alignment(Alignment::End)
                    ->verticallyAlignStart()
                ,
                TextColumn::make('item.code')
                    ->label(__('item.related.code.label'))
                    ->searchable()
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->verticallyAlignStart()
                ,
                TextColumn::make('item.name')
                    ->label(__('item.related.name.label') . ' | ' . __('common.description.label'))
                    ->wrapHeader()
                    ->description(function (PurchaseOrderItem $record): HtmlString {
                        $descriptionLines = collect([
                            filled($record->description) ? nl2br($record->description) : null,
                            $record->purchaseRequestItem?->purchaseRequest?->number
                            ? '# ' . $record->purchaseRequestItem->purchaseRequest->number
                            : null,
                        ])->filter();

                        return new HtmlString($descriptionLines->isNotEmpty()
                            ? $descriptionLines->implode('<br>')
                            : '');
                    })
                    ->searchable()
                    ->wrap()
                ,
                TextColumn::make('item.unit')
                    ->label('Unit')
                    ->color('gray')
                    ->verticallyAlignStart()
                ,
                TextColumn::make('qty')
                    ->numeric()
                    ->alignment(Alignment::End)
                    ->verticallyAlignStart()
                ,
                TextColumn::make('price')
                    ->label(__('purchase-order.purchase_order_item.price.label'))
                    ->numeric()
                    ->alignment(Alignment::End)
                    ->verticallyAlignStart()
                ,
                TextColumn::make('subtotal')
                    ->label(__('purchase-order.subtotal.label'))
                    ->state(fn(PurchaseOrderItem $record): float => $record->getLineTotalAmount())
                    ->formatStateUsing(fn(float $state): string => number_format($state, 2, ',', '.'))
                    ->alignment(Alignment::End)
                    ->verticallyAlignStart()
                ,
                TextColumn::make('received_qty')
                    ->label(__('goods-receive.goods_receive_items.received_qty.label'))
                    ->state(function (PurchaseOrderItem $record): float {
                        if ($record->relationLoaded('goodsReceiveItems')) {
                            return (float) $record->goodsReceiveItems
                                ->filter(fn($item) => $item->goodsReceive?->status?->value === \App\Enums\GoodsReceiveStatus::RECEIVED->value)
                                ->sum('qty');
                        }

                        return $record->getReceivedQty();
                    })
                    ->numeric()
                    ->color(fn(PurchaseOrderItem $record): string => self::getReceivedQtyColumnColor($record))
                    ->alignment(Alignment::End)
                    ->verticallyAlignStart()
                ,
            ])
            ->defaultSort('id', 'asc')
            ->striped()
            ->stackedOnMobile(false)
            ->paginated(false)
        ;
    }
}
