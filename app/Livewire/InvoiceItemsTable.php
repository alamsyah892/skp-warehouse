<?php

namespace App\Livewire;

use App\Models\InvoiceItem;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;

class InvoiceItemsTable extends TableWidget
{
    public $record;

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query(
                InvoiceItem::query()
                    ->with([
                        'item',
                        'goodsReceiveItem.goodsReceive',
                        'goodsReceiveItem.purchaseOrderItem.purchaseRequestItem.purchaseRequest',
                    ])
                    ->where('invoice_id', $this->record->id)
            )
            ->columns([
                TextColumn::make('sort')
                    ->label('#')
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->color('gray')
                    ->alignEnd()
                    ->width('1%')
                    ->verticallyAlignStart(),

                TextColumn::make('item.code')
                    ->label(__('item.code.label'))
                    ->wrapHeader()
                    ->weight(FontWeight::Bold)
                    ->size(TextSize::ExtraSmall)
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->verticallyAlignStart(),

                TextColumn::make('item.name')
                    ->label(__('item.name.label').' | '.__('common.description.label'))
                    ->wrapHeader()
                    ->description(function (InvoiceItem $record): HtmlString {
                        $purchaseRequestNumber = $record->goodsReceiveItem?->purchaseOrderItem?->purchaseRequestItem?->purchaseRequest?->number;

                        $descriptionLines = collect([
                            filled($record->description) ? nl2br(e($record->description)) : null,
                            $purchaseRequestNumber ? '# '.e($purchaseRequestNumber) : null,
                        ])->filter();

                        return new HtmlString(
                            $descriptionLines->isNotEmpty() ? $descriptionLines->implode('<br>') : ''
                        );
                    })
                    ->size(TextSize::ExtraSmall)
                    ->searchable()
                    ->wrap()
                    ->verticallyAlignStart(),

                TextColumn::make('item.unit')
                    ->label(__('item.unit.label'))
                    ->wrapHeader()
                    ->size(TextSize::ExtraSmall)
                    ->color('gray')
                    ->verticallyAlignStart(),

                TextColumn::make('qty')
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->wrap(false)
                    ->verticallyAlignStart(),

                TextColumn::make('price')
                    ->label(__('purchase-order.purchase_order_item.price.label'))
                    ->wrapHeader()
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->wrap(false)
                    ->verticallyAlignStart(),

                TextColumn::make('subtotal')
                    ->label(__('purchase-order.subtotal.label'))
                    ->wrapHeader()
                    ->state(fn (InvoiceItem $record): float => (float) $record->subtotal)
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->wrap(false)
                    ->verticallyAlignStart(),
            ])
            ->defaultSort('id', 'asc')
            ->striped()
            ->stackedOnMobile(false)
            ->paginated(false);
    }
}
