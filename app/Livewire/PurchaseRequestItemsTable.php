<?php

namespace App\Livewire;

use App\Enums\PurchaseRequestStatus;
use App\Models\PurchaseRequestItem;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;

class PurchaseRequestItemsTable extends TableWidget
{
    public $record;

    public static function getOrderedQtyColumnColor(PurchaseRequestItem $purchaseRequestItem): string
    {
        $orderedQty = $purchaseRequestItem->getOrderedQty();
        $requestedQty = (float) $purchaseRequestItem->qty;

        return match (true) {
            $orderedQty <= 0 => 'danger',
            $orderedQty < $requestedQty => 'warning',
            default => 'success',
        };
    }

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
                    ->alignment(Alignment::End)
                    ->grow(false)
                ,
                TextColumn::make('item.code')
                    ->label('SKU')
                    ->searchable()
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->grow(false)
                ,
                TextColumn::make('item.name')
                    ->label('Nama Item | Deskripsi')
                    ->wrapHeader()
                    ->description(fn($record): HtmlString => new HtmlString(nl2br($record->description)))
                    ->searchable()
                    ->wrap()
                ,
                TextColumn::make('item.unit')
                    ->label('Unit')
                    ->color('gray')
                    ->grow(false)
                ,
                TextColumn::make('qty')
                    ->numeric()
                    ->alignment(Alignment::End)
                    ->grow(false)
                ,
                TextColumn::make('ordered_qty')
                    ->label('Dipesan')
                    ->wrapHeader()
                    ->state(fn(PurchaseRequestItem $record): float => $record->getOrderedQty())
                    ->numeric()
                    ->color(fn(PurchaseRequestItem $record): string => self::getOrderedQtyColumnColor($record))
                    ->weight(FontWeight::Bold)
                    ->alignment(Alignment::End)
                    ->visible(
                        fn() =>
                        $this->record->status === PurchaseRequestStatus::ORDERED ||
                        $this->record->status === PurchaseRequestStatus::FINISHED
                    )
                    ->grow(false)
                ,
                // TextColumn::make('remaining_qty')
                //     ->label('Sisa')
                //     ->wrapHeader()
                //     ->state(fn($record) => $record->getRemainingQty())
                //     ->numeric()
                //     ->alignment(Alignment::End)
                //     ->visible(
                //         fn() =>
                //         $this->record->status === PurchaseRequestStatus::ORDERED ||
                //         $this->record->status === PurchaseRequestStatus::FINISHED
                //     )
                //     ->grow(false)
                // ,
            ])
            ->defaultSort('id', 'asc')

            ->striped()
            ->stackedOnMobile()

            ->paginated(false)
        ;
    }
}
