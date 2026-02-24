<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Filament\Components\Tables\TimestampPanel;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RolesTable
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
                            ->weight(FontWeight::Bold)
                        ,
                        TextColumn::make('guard_name')
                            ->searchable()
                            ->sortable()
                            ->badge()
                            ->grow(false)
                            ->visible(fn() => auth()->user()?->hasRole('Project Owner'))
                        ,
                    ]),
                    Split::make([
                        Split::make([
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
                            TextColumn::make('users.name')
                                ->description('Users:', position: 'above')
                                ->limitList(3)
                            ,
                        ]),
                    ])->from('md'),
                ])->space(2),
                Panel::make([
                    TimestampPanel::make(),
                ])->collapsible(),
            ])
            ->filters([
                TrashedFilter::make()->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->before(function ($record, DeleteAction $action) {
                    if ($record->users()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Role cannot be deleted because it still has Users.')
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
