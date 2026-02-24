<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Filament\Components\Tables\TimestampPanel;
use App\Models\Company;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontFamily;
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

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        TextColumn::make('alias')
                            ->label('Bussines Name / Alias')
                            ->searchable()
                            ->sortable()
                            ->size(TextSize::Large)
                            ->grow(false)
                        ,
                        IconColumn::make('is_active')
                            ->label('Status')
                            ->sortable()
                            ->tooltip(fn($state) => Company::STATUS_LABELS[$state] ?? '-')
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

                    TextColumn::make('name')
                        ->label('Company Name')
                        ->searchable()
                        ->sortable()
                        ->description(fn($record): string => $record->description)
                    ,

                    Stack::make([
                        TextColumn::make('address')
                            ->searchable()
                            ->color('gray')
                        ,
                        TextColumn::make('city')
                            ->searchable()
                            ->color('gray')
                        ,
                        TextColumn::make('post_code')
                            ->searchable()
                            ->color('gray')
                        ,
                    ]),
                ])->space(2),
                Panel::make([
                    Stack::make([
                        Split::make([
                            TextColumn::make('contact_person')
                                ->description("Contact person: ", position: 'above')
                                ->searchable()
                            ,
                            TextColumn::make('contact_person_position')
                                ->description("Contact person position: ", position: 'above')
                            ,
                        ]),
                        Split::make([
                            TextColumn::make('phone')
                                ->description("Phone: ", position: 'above')
                                ->searchable()
                            ,
                            TextColumn::make('fax')
                                ->description("Fax: ", position: 'above')
                                ->searchable()
                            ,
                        ]),
                        Split::make([
                            TextColumn::make('email')
                                ->description("Email address: ", position: 'above')
                                ->searchable()
                            ,
                            TextColumn::make('website')
                                ->description("Website: ", position: 'above')
                                ->searchable()
                            ,
                        ]),
                        Split::make([
                            TextColumn::make('tax_number')
                                ->description("Tax Number: ", position: 'above')
                                ->searchable()
                            ,
                        ]),

                        TimestampPanel::make(),

                        TextColumn::make('warehouses.name')
                            ->description("Warehouses: ", position: 'above')
                            ->badge()
                            ->limitList(3)
                        ,

                        TextColumn::make('divisions.name')
                            ->description("Divisions: ", position: 'above')
                            ->badge()
                            ->limitList(3)
                        ,

                        TextColumn::make('projects.name')
                            ->description("Projects: ", position: 'above')
                            ->badge()
                            ->limitList(3)
                        ,

                        TextColumn::make('purchase_requests_count')
                            ->description("PR count: ", position: 'above')
                            ->sortable()
                        ,

                        TextColumn::make('banks.name')
                            ->description("Banks: ", position: 'above')
                            ->badge()
                            ->limitList(3)
                        ,
                    ])->space(2),
                ])->collapsible(),
            ])
            ->filters([
                SelectFilter::make('warehouses')
                    ->relationship(
                        'warehouses',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code')
                    )
                    ->multiple()
                    ->preload()
                ,
                SelectFilter::make('divisions')
                    ->relationship(
                        'divisions',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code')
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
                    ->options(Company::STATUS_LABELS)
                    ->native(false)
                ,

                TrashedFilter::make()->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->before(function ($record, DeleteAction $action) {
                    if ($record->purchaseRequests()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Company cannot be deleted because it has Purchase Requests.')
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