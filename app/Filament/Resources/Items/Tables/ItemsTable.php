<?php

namespace App\Filament\Resources\Items\Tables;

use App\Filament\Components\Tables\TimestampPanel;
use App\Models\Item;
use App\Models\ItemCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        TextColumn::make('name')
                            ->searchable()
                            ->sortable()
                            ->size(TextSize::Large)
                            ->grow(false)
                        ,
                        IconColumn::make('is_active')
                            ->label('Status')
                            ->sortable()
                            ->tooltip(fn($state) => Item::STATUS_LABELS[$state] ?? '-')
                            ->boolean()
                            ->trueIcon(Heroicon::CheckBadge)
                            ->falseIcon(Heroicon::ExclamationTriangle)
                            ->trueColor('success')
                            ->falseColor('warning')
                        ,
                        TextColumn::make('code')
                            ->searchable()
                            ->sortable()
                            ->badge()
                            ->fontFamily(FontFamily::Mono)
                            ->size(TextSize::Large)
                            ->grow(false)
                        ,
                    ]),
                    TextColumn::make('category.parent_full_path')
                        ->color('gray')
                    ,

                    TextColumn::make('description')
                        ->placeholder('-')
                        ->color('gray')
                    ,
                ])->space(2),
                Panel::make([
                    Stack::make([
                        Split::make([
                            TextColumn::make('unit')
                                ->description("Unit: ", position: 'above')
                            ,
                            TextColumn::make('type')
                                ->description("Type: ", position: 'above')
                                ->formatStateUsing(fn($state) => Item::TYPE_LABELS[$state] ?? '-')
                                ->badge()
                                ->color(fn($state) => $state == Item::TYPE_STOCKABLE ? 'success' : 'warning')
                            ,
                        ]),

                        TimestampPanel::make(),

                        TextColumn::make('purchase_request_items_count')
                            ->description("PR count: ", position: 'above')
                            ->sortable()
                        ,
                    ])->space(2),
                ])->collapsible(),
            ])
            ->filters([
                Filter::make('category')
                    ->form([
                        Select::make('category_id')
                            ->label('Category')
                            ->options(
                                ItemCategory::query()
                                    ->whereDoesntHave('children') // leaf only
                                    ->orderBy('parent_id')
                                    ->orderBy('name')
                                    ->get()
                                    ->groupBy(fn($category) => $category->parent_path)
                                    ->mapWithKeys(fn($group) => [
                                        $group->first()->parent_path => $group->pluck('name', 'id')->toArray(),
                                    ])
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload()
                        ,
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['category_id'])) {
                            return;
                        }

                        $query->where('category_id', $data['category_id']);
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['category_id'])) {
                            return null;
                        }

                        return 'Category: ' . ItemCategory::find($data['category_id'])?->parent_full_path;
                    })
                ,
                SelectFilter::make('type')
                    ->options(Item::TYPE_LABELS)
                    ->native(false)
                ,

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options(Item::STATUS_LABELS)
                    ->native(false)
                ,

                TrashedFilter::make()->native(false),

                Filter::make('unused_in_pr')
                    ->label('Belum ada di PR')
                    ->indicator('PR count: 0')
                    ->query(
                        fn(Builder $query) =>
                        $query->whereDoesntHave('purchaseRequestItems')
                    )
                ,
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
        ;
    }
}