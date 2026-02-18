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
                        TextColumn::make('code')
                            ->searchable()
                            ->sortable()
                            ->badge()
                            ->color('info')
                            ->fontFamily(FontFamily::Mono)
                            ->size(TextSize::Large)
                        ,

                        IconColumn::make('is_active')
                            ->label('Status')
                            ->sortable()
                            ->tooltip(fn($state) => Warehouse::STATUS_LABELS[$state] ?? '-')
                            ->boolean()
                            ->trueIcon(Heroicon::OutlinedCheckBadge)
                            ->falseIcon(Heroicon::OutlinedExclamationTriangle)
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->grow(false)
                        ,
                    ]),
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable()
                        ->description(fn($record): string => $record->description)
                        ->weight(FontWeight::Bold)
                    ,
                ])->space(2),
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
                        ImageColumn::make('users.avatar_url')
                            ->limit(3)
                            ->limitedRemainingText()
                            ->circular()
                            ->stacked()
                            ->grow(false)
                            ->disk('public')
                            ->defaultImageUrl(fn($record) => $record->users?->count() ? url('avatars/ic_default_user.png') : false)
                            ->extraImgAttributes([
                                'alt' => 'Image',
                                'loading' => 'lazy',
                            ])
                        ,
                        TextColumn::make('users.name')
                            ->description('Users:', position: 'above')
                            ->limitList(3)
                        ,
                    ])->space(2),
                ])->collapsible(),
            ])
            ->filters([
                SelectFilter::make('companies')->relationship('companies', 'alias')->multiple()->preload(),
                SelectFilter::make('projects')->relationship('projects', 'name')->multiple()->searchable()->preload(),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options(Warehouse::STATUS_LABELS)
                    ->native(false)
                ,

                TrashedFilter::make()->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->before(function ($record, DeleteAction $action) {
                    if ($record->companies()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Warehouse cannot be deleted because it still has Companies.')
                            ->danger()
                            ->send()
                        ;
                        $action->cancel();
                    } elseif ($record->projects()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Warehouse cannot be deleted because it still has Projects.')
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