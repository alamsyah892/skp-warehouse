<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Components\Tables\TimestampPanel;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        ImageColumn::make('avatar_url')
                            ->circular()
                            ->grow(false)
                            ->disk('public')
                            ->defaultImageUrl(fn($record) => $record->users?->count() ? url('avatars/ic_default_user.png') : false)
                            ->extraImgAttributes([
                                'alt' => 'Image',
                                'loading' => 'lazy',
                            ])
                        ,
                        TextColumn::make('name')
                            ->searchable()
                            ->sortable()
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                        ,
                        IconColumn::make('email_verified_at')
                            ->label('Status')
                            ->sortable()
                            ->tooltip(fn($state) => $state ? 'Verified' : 'Unverified')
                            ->boolean()
                            ->trueIcon(Heroicon::OutlinedCheckBadge)
                            ->trueColor('success')
                            ->grow(false)
                        ,
                    ]),
                    TextColumn::make('email')
                        ->searchable()
                        ->icon(Heroicon::Envelope)
                    ,
                    TextColumn::make('roles.name')
                        ->limitList(3)
                        ->badge()
                        ->icon(Heroicon::UserGroup)
                    ,
                    TextColumn::make('warehouses.name')
                        ->limitList(3)
                        ->badge()
                        ->icon(Heroicon::HomeModern)
                    ,
                ])->space(2),
                Panel::make([
                    Stack::make([
                        TextColumn::make('email_verified_at')
                            ->description('Email verified at:', position: 'above')
                            ->dateTime()
                        ,

                        TimestampPanel::make(),
                    ])->space(2),

                ])->collapsible(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                ,
                SelectFilter::make('warehouses')
                    ->relationship('warehouses', 'name')
                    ->multiple()
                    ->preload()
                ,
                TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable()
                    ->trueLabel('Verified')
                    ->falseLabel('Unverified')
                    ->native(false)
                ,

                TrashedFilter::make()->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->before(function ($record, DeleteAction $action) {
                    if ($record->roles()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This User cannot be deleted because it still has Roles.')
                            ->danger()
                            ->send()
                        ;
                        $action->cancel();
                    } elseif ($record->warehouses()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This User cannot be deleted because it still has Warehouses.')
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
