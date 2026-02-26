<?php

namespace App\Filament\Resources\Vendors\Tables;

use App\Filament\Components\Tables\TimestampPanel;
use App\Models\ItemCategory;
use App\Models\Vendor;
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
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class VendorsTable
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
                            ->tooltip(fn($state) => Vendor::STATUS_LABELS[$state] ?? '-')
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
                ]),
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

                        TextColumn::make('itemCategories.name')
                            ->description("Item categories (Domain): ", position: 'above')
                            ->badge()
                            ->limitList(3)
                        ,
                    ])->space(2),
                ])->collapsible(),
            ])
            ->filters([
                SelectFilter::make('itemCategories')
                    ->label('Item Categories (Domain)')
                    ->relationship(
                        'itemCategories',
                        'name',
                        modifyQueryUsing: fn($query) =>
                        $query
                            ->where('level', ItemCategory::LEVEL_DOMAIN)
                            ->where('allow_po', true)
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                ,

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options(Vendor::STATUS_LABELS)
                    ->native(false)
                ,

                TrashedFilter::make()->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->before(function ($record, DeleteAction $action) {
                    if ($record->itemCategories()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Vendor cannot be deleted because it still has Item categories.')
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
