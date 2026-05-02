<?php

namespace App\Livewire;

use App\Models\GoodsIssueItem;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;

class GoodsIssueItemsTable extends TableWidget
{
    public $record;

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query(
                GoodsIssueItem::query()
                    ->with([
                        'item'
                    ])
                    ->whereHas(
                        'goodsIssue',
                        fn($query) => $query->whereKey($this->record->id)
                    )
            )
            ->columns([
                TextColumn::make('sort')
                    ->label('#')
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->color('gray')
                    ->alignEnd()
                    ->width('1%')
                    ->verticallyAlignStart()
                ,
                TextColumn::make('item.code')
                    ->label(__('item.code.label'))
                    ->wrapHeader()
                    ->weight(FontWeight::Bold)
                    ->size(TextSize::ExtraSmall)
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->verticallyAlignStart()
                ,
                TextColumn::make('item.name')
                    ->label(__('item.name.label') . ' | ' . __('common.description.label'))
                    ->wrapHeader()
                    ->description(fn($record): HtmlString => new HtmlString(nl2br($record->description)))
                    ->size(TextSize::ExtraSmall)
                    ->searchable()
                    ->wrap()
                    ->verticallyAlignStart()
                ,
                TextColumn::make('item.unit')
                    ->label(__('item.unit.label'))
                    ->wrapHeader()
                    ->size(TextSize::ExtraSmall)
                    ->color('gray')
                    ->verticallyAlignStart()
                ,
                TextColumn::make('qty')
                    ->numeric()
                    ->size(TextSize::ExtraSmall)
                    ->alignEnd()
                    ->wrap(false)
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