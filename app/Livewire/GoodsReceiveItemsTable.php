<?php

namespace App\Livewire;

use App\Models\GoodsReceiveItem;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;

class GoodsReceiveItemsTable extends TableWidget
{
    public $record;

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query(
                GoodsReceiveItem::query()
                    ->with([
                        'item',
                        'purchaseOrderItem.purchaseRequestItem.purchaseRequest',
                    ])
                    ->where('goods_receive_id', $this->record->id),
            )
            ->columns([
                TextColumn::make('sort')
                    ->label('#')
                    ->numeric()
                    ->alignment(Alignment::End)
                    ->verticallyAlignStart(),
                TextColumn::make('item.code')
                    ->label(__('item.related.code.label'))
                    ->searchable()
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->verticallyAlignStart(),
                TextColumn::make('item.name')
                    ->label(__('item.related.name.label') . ' | ' . __('common.description.label'))
                    ->wrapHeader()
                    ->description(function (GoodsReceiveItem $record): HtmlString {
                        $purchaseRequestNumber = $record->purchaseOrderItem?->purchaseRequestItem?->purchaseRequest?->number;

                        $descriptionLines = collect([
                            filled($record->description) ? nl2br($record->description) : null,
                            $purchaseRequestNumber ? '# ' . $purchaseRequestNumber : null,
                        ])->filter();

                        return new HtmlString($descriptionLines->isNotEmpty()
                            ? $descriptionLines->implode('<br>')
                            : '');
                    })
                    ->searchable()
                    ->wrap(),
                TextColumn::make('item.unit')
                    ->label('Unit')
                    ->color('gray')
                    ->verticallyAlignStart(),
                TextColumn::make('qty')
                    ->label(__('goods-receive.qty.label'))
                    ->numeric()
                    ->alignment(Alignment::End)
                    ->verticallyAlignStart(),
            ])
            ->defaultSort('id', 'asc')
            ->striped()
            ->stackedOnMobile(false)
            ->paginated(false);
    }
}

