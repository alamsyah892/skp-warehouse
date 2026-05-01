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
                    ->with(['item'])
                    ->where('goods_issue_id', $this->record->id),
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
                    ->description(function (GoodsIssueItem $record): HtmlString {
                        return new HtmlString(
                            filled($record->description) ? nl2br(e($record->description)) : ''
                        );
                    })
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
            ])
            ->defaultSort('sort', 'asc')
            ->striped()
            ->stackedOnMobile(false)
            ->paginated(false);
    }
}
