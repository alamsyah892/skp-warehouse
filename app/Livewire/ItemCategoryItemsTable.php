<?php

namespace App\Livewire;

use App\Filament\Resources\Items\ItemResource;
use App\Models\Item;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class ItemCategoryItemsTable extends TableWidget
{
    public $record;

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query(
                Item::query()
                    ->where('category_id', $this->record->id)
                    ->withCount(['purchaseRequestItems' => fn($query) => $query->forUserWarehouses(Auth::user())])
            )
            ->columns([
                TextColumn::make('name')
                    ->description(fn($record): string => $record->description)
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->wrap()
                ,
                IconColumn::make('is_active')
                    ->label('Status')
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
                    ->icon(Heroicon::Hashtag)
                    ->iconColor('primary')
                    ->copyable()
                    ->copyMessage('Email address copied')
                ,

                TextColumn::make('unit')
                    ->color('gray')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('type')
                    ->formatStateUsing(fn($state) => Item::TYPE_LABELS[$state] ?? '-')
                    ->badge()
                    ->color(fn($state) => $state == Item::TYPE_STOCKABLE ? 'success' : 'warning')
                    ->toggleable(isToggledHiddenByDefault: true)
                ,

                TextColumn::make('purchase_request_items_count')
                    ->label('PR Count')
                    ->wrapHeader()
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true)
                ,

                TextColumn::make('created_at')
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->color('gray')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('updated_at')
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->color('gray')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('deleted_at')
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->placeholder('-')
                    ->color('gray')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
            ])
            ->filters([
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
                        fn($query) =>
                        $query->whereDoesntHave('purchaseRequestItems')
                    )
                ,
            ])
            ->recordActions([
                ViewAction::make()->hiddenLabel()
                    ->url(
                        fn($record) => ItemResource::getUrl('view', [
                            'record' => $record->id,
                        ])
                    )
                ,
            ], position: RecordActionsPosition::BeforeColumns)

            ->striped()
            ->stackedOnMobile()

            ->contentGrid([])
            ->paginated([5, 10, 25, 50, 100])
            ->paginationMode(PaginationMode::Default)
            ->defaultPaginationPageOption(10)
        ;
    }
}
