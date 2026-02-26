<?php

namespace App\Filament\Resources\Warehouses\Tables;

use App\Filament\Components\Tables\TimestampPanel;
use App\Models\Warehouse;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class WarehousesTable
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
                            ->weight(FontWeight::Bold)
                            ->grow(false)
                        ,
                        IconColumn::make('is_active')
                            ->label('Status')
                            ->sortable()
                            ->tooltip(fn($state) => Warehouse::STATUS_LABELS[$state] ?? '-')
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
                            ->grow(false)
                        ,
                    ]),
                    TextColumn::make('description')
                        ->placeholder('-')
                        ->color('gray')
                    ,
                ]),
                Panel::make([
                    Stack::make([
                        TimestampPanel::make(),

                        TextColumn::make('companies.alias')
                            ->description("Companies: ", position: 'above')
                            ->badge()
                            ->limitList(3)
                        ,
                        TextColumn::make('projects.name')
                            ->description("Projects: ", position: 'above')
                            ->badge()
                            ->limitList(3)
                        ,
                        TextColumn::make('users.name')
                            ->description('Users:', position: 'above')
                            ->limitList(3)
                        ,
                        ImageColumn::make('users.avatar_url')
                            ->limit(3)
                            ->limitedRemainingText()
                            ->circular()
                            ->stacked()
                            ->grow(false)
                            ->disk('public')
                            ->extraImgAttributes([
                                'alt' => 'Image',
                                'loading' => 'lazy',
                            ])
                        ,

                        TextColumn::make('purchase_requests_count')
                            ->description("PR count: ", position: 'above')
                            ->sortable()
                        ,
                    ])->space(2),
                ])->collapsible(),
            ])
            ->filters([
                SelectFilter::make('companies')
                    ->relationship(
                        'companies',
                        'alias',
                        fn($query) => $query->orderBy('alias')->orderBy('code')
                    )
                    ->multiple()
                    ->preload()
                ,
                SelectFilter::make('projects')
                    ->relationship(
                        'projects',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code')
                    )
                    ->multiple()
                    ->preload()
                ,

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options(Warehouse::STATUS_LABELS)
                    ->native(false)
                ,

                TrashedFilter::make()->native(false),

                Filter::make('unused_in_pr')
                    ->label('Belum ada di PR')
                    ->indicator('PR count: 0')
                    ->query(
                        fn($query) =>
                        $query->whereDoesntHave('purchaseRequests')
                    )
                ,
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->before(function ($record, DeleteAction $action) {
                    if ($record->purchaseRequests()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Warehouse cannot be deleted because it has Purchase Requests.')
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