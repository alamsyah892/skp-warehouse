<?php

namespace App\Filament\Resources\Couriers\Tables;

use App\Filament\Components\Tables\TimestampPanel;
use App\Models\Courier;
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

class CouriersTable
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
                            ->tooltip(fn($state) => Courier::STATUS_LABELS[$state] ?? '-')
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
                    ])->space(2),
                ])->collapsible(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options(Courier::STATUS_LABELS)
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