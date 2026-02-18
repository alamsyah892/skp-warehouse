<?php

namespace App\Filament\Resources\Banks\Tables;

use App\Filament\Components\Tables\TimestampPanel;
use App\Models\Bank;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BanksTable
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
                            ->tooltip(fn($state) => Bank::STATUS_LABELS[$state] ?? '-')
                            ->boolean()
                            ->trueIcon(Heroicon::OutlinedCheckBadge)
                            ->falseIcon(Heroicon::OutlinedExclamationTriangle)
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->grow(false)
                        ,
                    ]),

                    Stack::make([
                        TextColumn::make('company.alias')
                            ->searchable()
                            ->sortable()
                            ->weight(FontWeight::Bold)
                            ->color('gray')
                        ,
                        TextColumn::make('company.name')
                            ->searchable()
                            ->color('gray')
                        ,
                    ]),

                    Stack::make([
                        TextColumn::make('name')
                            ->searchable()
                            ->sortable()
                            ->description(fn($record): string => $record->description)
                            ->weight(FontWeight::Bold)
                        ,

                        TextColumn::make('account_number')
                            ->searchable()
                            ->placeholder('-')
                            ->color('gray')
                        ,
                    ]),
                ])->space(2),
                Panel::make([
                    Stack::make([
                        TextColumn::make('currency.code')
                            ->description('Currency: ', position: 'above')
                            ->badge()
                            ->color('info')
                            ->fontFamily(FontFamily::Mono)
                            ->size(TextSize::Large)
                        ,
                        TextColumn::make('balance')
                            ->description('Balance: ', position: 'above')
                        ,

                        TimestampPanel::make(),
                    ])->space(2),
                ])->collapsible(),
            ])
            ->filters([
                SelectFilter::make('company')->relationship('company', 'alias')->multiple()->preload(),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options(Bank::STATUS_LABELS)
                    ->native(false)
                ,

                TrashedFilter::make()->native(false),
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