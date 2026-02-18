<?php

namespace App\Filament\Resources\ItemCategories\Tables;

use App\Filament\Components\Tables\TimestampPanel;
use App\Models\ItemCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ItemCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        TextColumn::make('code')
                            ->searchable()
                            ->sortable()
                            ->badge()
                            ->fontFamily(FontFamily::Mono)
                            ->size(TextSize::Large)
                            ->color('info')
                        ,

                        TextColumn::make('level')
                            ->formatStateUsing(fn(int|null $state) => ItemCategory::LEVEL_LABELS[$state] ?? '-')
                            ->badge()
                            ->color(fn(int|null $state) => ItemCategory::LEVEL_COLOR[$state] ?? 'default')
                            ->grow(false)
                        ,
                    ]),
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable()
                        ->description(fn($record): string => $record->description)
                        ->weight(FontWeight::Bold)
                    ,

                    TextColumn::make('parent_path')
                        ->searchable()
                        ->sortable()
                        ->color('gray')
                    ,
                ])->space(2),
                Panel::make([
                    Stack::make([
                        TextColumn::make('allow_po')
                            ->description('Allow PO: ', position: 'above')
                            ->sortable()
                            ->formatStateUsing(fn($state) => $state ? 'Allowed' : 'Blocked')
                            ->color(fn(bool $state) => $state ? 'success' : 'danger')
                        ,

                        TimestampPanel::make(),

                        TextColumn::make('items.name')
                            ->description("Items ", position: 'above')
                            ->badge()
                            ->limitList(3)
                        ,
                    ])->space(2),
                ])->collapsible(),
            ])
            ->filters([
                Filter::make('domain_category')
                    ->form([
                        /* ================= DOMAIN ================= */
                        Select::make('domain_id')
                            ->label('Domain')
                            ->options(ItemCategory::where('level', ItemCategory::LEVEL_DOMAIN)->pluck('name', 'id'))
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(fn($set) => $set('category_id', null))
                        ,

                        /* ================= CATEGORY ================= */
                        Select::make('category_id')
                            ->label('Category')
                            ->options(function ($get) {
                                $domainId = $get('domain_id');

                                return ItemCategory::where('level', ItemCategory::LEVEL_CATEGORY)
                                    ->when($domainId, fn($q) => $q->where('parent_id', $domainId))
                                    ->pluck('name', 'id')
                                ;
                            })
                            ->searchable()
                            ->reactive()
                        // ->disabled(fn($get) => blank($get('domain_id')))
                        ,
                    ])
                    ->query(function (Builder $query, array $data) {
                        /* ===== DOMAIN ===== */
                        if (!empty($data['domain_id'])) {
                            $query->where(function ($q) use ($data) {
                                $q->where('id', $data['domain_id'])
                                    ->orWhere('parent_id', $data['domain_id'])
                                    ->orWhereHas(
                                        'parent.parent',
                                        fn($qq) => $qq->where('id', $data['domain_id'])
                                    )
                                ;
                            });
                        }

                        /* ===== CATEGORY ===== */
                        if (!empty($data['category_id'])) {
                            $query->where(function ($q) use ($data) {
                                $q->where('id', $data['category_id'])
                                    ->orWhere('parent_id', $data['category_id'])
                                ;
                            });
                        }
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (!empty($data['domain_id'])) {
                            $indicators[] = 'Domain: ' . ItemCategory::find($data['domain_id'])?->name;
                        }

                        if (!empty($data['category_id'])) {
                            $indicators[] = 'Category: ' . ItemCategory::find($data['category_id'])?->name;
                        }

                        return $indicators;
                    })
                ,

                SelectFilter::make('allow_po')
                    ->label('Allow PO')
                    ->options([1 => 'Allowed', 0 => 'Blocked',])
                    ->native(false)
                ,

                TrashedFilter::make()->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->before(function (Model $record, DeleteAction $action) {
                    if (!$record->isLeaf()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Item Category cannot be deleted because it still has Sub Categories.')
                            ->danger()
                            ->send()
                        ;
                        $action->cancel();
                    } elseif ($record->items()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Item Category cannot be deleted because it still has Items.')
                            ->danger()
                            ->send()
                        ;
                        $action->cancel();
                    }
                }),
                RestoreAction::make(),
            ])
        ;
    }
}
