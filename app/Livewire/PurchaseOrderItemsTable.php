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

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query(
                PurchaseOrderItem::query()
                    ->with([
                        'item',
                        'purchaseRequestItem.purchaseRequest',
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
                    ->label('SKU')
                    ->searchable()
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->verticallyAlignStart()
                ,
                TextColumn::make('item.name')
                    ->label('Nama Item | Deskripsi')
                    ->wrapHeader()
                    ->description(function (PurchaseOrderItem $record): HtmlString {
                        $descriptionLines = collect([
                            filled($record->description) ? e($record->description) : null,
                            $record->purchaseRequestItem?->purchaseRequest?->number
                            ? '# ' . e($record->purchaseRequestItem->purchaseRequest->number)
                            : null,
                        ])->filter();

                        return new HtmlString($descriptionLines->isNotEmpty()
                            ? $descriptionLines->implode('<br>')
                            : '-');
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
                    ->label('Harga')
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
            ])
            ->defaultSort('id', 'asc')
            ->striped()
            ->stackedOnMobile(false)
            ->paginated(false)
        ;
    }
}
