<?php

namespace App\Livewire;

use App\Models\PurchaseRequestItem;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;

class PurchaseRequestItemsTable extends TableWidget
{
    public $record;

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query(
                PurchaseRequestItem::query()
                    ->with([
                        'item',
                    ])
                    ->where('purchase_request_id', $this->record->id)
            )
            ->columns([
                TextColumn::make('sort')
                    ->label('#')
                    ->numeric()
                    ->color('gray')
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->verticallyAlignStart()
                    ->width('1%')
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
                    ->description(fn($record): HtmlString => new HtmlString(nl2br($record->description)))
                    ->searchable()
                    ->size(TextSize::ExtraSmall)
                    ->verticallyAlignStart()
                    ->wrap()
                ,
                TextColumn::make('item.unit')
                    ->label(__('item.unit.label'))
                    ->wrapHeader()
                    ->color('gray')
                    ->size(TextSize::ExtraSmall)
                    ->verticallyAlignStart()
                ,
                TextColumn::make('qty')
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->wrap(false)
                    ->verticallyAlignStart()
                ,
                TextColumn::make('ordered_qty')
                    ->label(__('purchase-request.purchase_request_items.ordered_qty_label'))
                    ->wrapHeader()
                    ->state(fn(PurchaseRequestItem $record): float|null => $record->getOrderedQty() > 0 ? $record->getOrderedQty() : null)
                    ->placeholder('-')
                    ->numeric()
                    ->color(fn(PurchaseRequestItem $record): string => $record->getOrderedQtyColor())
                    ->weight(FontWeight::Bold)
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->sortable()
                    ->wrap(false)
                    ->verticallyAlignStart()
                    ->visible(fn() => $this->record && $this->record->purchaseOrders()->exists())
                ,
            ])
            ->defaultSort('sort', 'asc')
            ->striped()
            ->stackedOnMobile(false)
            ->paginated(false)
        ;
    }
}
