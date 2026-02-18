<?php

namespace App\Filament\Resources\Vendors\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\Vendor;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;

class VendorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->tabs([
                Tab::make('Data')->schema([
                    Grid::make()
                        ->columns([
                            'default' => 1,
                            'lg' => 1,
                            'xl' => 1,
                            '2xl' => 4,
                        ])
                        ->schema([
                            Section::make() // left
                                ->columnSpan([
                                    '2xl' => 3,
                                ])
                                ->contained(false)
                                ->schema([
                                    Fieldset::make('Vendor')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('code')
                                                ->badge()
                                                ->color('info')
                                                ->placeholder('-')
                                                ->fontFamily(FontFamily::Mono)
                                                ->size(TextSize::Large)
                                            ,
                                            TextEntry::make('name'),
                                            TextEntry::make('description')
                                                ->placeholder('-')
                                                ->columnSpanFull()
                                            ,

                                            TextEntry::make('address')
                                                ->columnSpanFull()
                                            ,
                                            TextEntry::make('city'),
                                            TextEntry::make('post_code')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('contact_person')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('contact_person_position')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('phone')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('fax')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('email')
                                                ->label('Email address')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('website')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('tax_number')
                                                ->placeholder('-')
                                            ,
                                        ])
                                    ,
                                ])
                            ,
                            Section::make() // right
                                ->contained(false)
                                ->schema([
                                    Fieldset::make('Configuration & Information')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('is_active')
                                                ->label('Status')
                                                ->formatStateUsing(fn($state) => Vendor::STATUS_LABELS[$state] ?? '-')
                                                ->badge()
                                                ->color(fn(bool $state) => $state == Vendor::STATUS_ACTIVE ? 'success' : 'danger')
                                            ,

                                            Grid::make()
                                                ->columnSpanFull()
                                                ->schema([
                                                    TextEntry::make('created_at')->date(),
                                                    TextEntry::make('updated_at')->date(),
                                                    TextEntry::make('deleted_at')->date()->visible(fn($state) => $state != null),
                                                ])
                                            ,
                                        ])
                                    ,

                                    Fieldset::make('Related Data')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('itemCategories.name')
                                                ->label(fn($record) => 'Item categories (Domain) (' . ($record->itemCategories?->count() ?? 0) . ')')
                                                ->badge()
                                                ->columnSpanFull()
                                            ,
                                        ])
                                    ,
                                ])
                            ,
                        ])
                    ,
                ]),
                ActivityLogTab::make('Logs'),
            ])->columnSpanFull(),
        ]);
    }
}
