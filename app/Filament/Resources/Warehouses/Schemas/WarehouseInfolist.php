<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\Warehouse;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class WarehouseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    // 'lg' => 1,
                    'xl' => 4,
                    '2xl' => 4,
                ])
                ->schema([
                    Grid::make() // left / 1
                        ->columnSpan([
                            'xl' => 3,
                            '2xl' => 3,
                        ])
                        ->schema([
                            static::dataSection(), // 1.1

                            static::tabSection(), // 1.2
                        ])
                    ,

                    Grid::make() // right / 2
                        ->columnSpan([
                            'xl' => 1,
                            '2xl' => 1,
                        ])
                        ->schema([
                            static::otherInfoSection(), // 2.1

                            static::relatedDataSection(), // 2.2
                        ])
                    ,
                ])
            ,
        ]);
    }

    protected static function dataSection(): Section
    {
        return Section::make('Warehouse Information')
            ->icon(Heroicon::BuildingOffice2)
            ->iconColor('primary')
            ->description('Informasi utama dan identitas dasar gudang.')
            // ->afterHeader([
            //     Action::make('edit')
            //         ->label('Edit')
            //         ->icon(Heroicon::PencilSquare)
            //         ->url(fn($record) => WarehouseResource::getUrl('edit', ['record' => $record]))
            //     ,
            // ])
            ->collapsible()
            ->columnSpanFull()
            ->columns(3)
            ->compact()
            ->schema([

            ])
        ;
    }

    protected static function otherInfoSection(): Section
    {
        return Section::make('Other Information')
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->description('Informasi lain terkait gudang.')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([

            ])
        ;
    }

    protected static function relatedDataSection(): Section
    {
        return Section::make('Related Data')
            ->icon(Heroicon::Link)
            ->iconColor('primary')
            ->description('Daftar entitas yang terhubung dengan gudang ini.')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([

            ])
        ;
    }

    protected static function tabSection(): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                Tab::make('PR History')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->badge(fn($record) => $record->purchase_requests_count ?: null)
                    ->schema([

                    ])
                ,

                ActivityLogTab::make('Logs'),
            ])
        ;
    }


    public static function _configure(Schema $schema): Schema
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
                                    Fieldset::make('Warehouse')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('code')
                                                ->badge()
                                                ->color('info')
                                                ->fontFamily(FontFamily::Mono)
                                                ->size(TextSize::Large)
                                            ,
                                            TextEntry::make('name'),
                                            TextEntry::make('description')
                                                ->placeholder('-')
                                                ->columnSpanFull()
                                            ,
                                        ])
                                    ,

                                    Fieldset::make('Warehouse Addresses')
                                        ->columnSpanFull()
                                        ->columns(1)
                                        ->schema([
                                            RepeatableEntry::make('addresses')
                                                ->hiddenLabel()
                                                ->columns(3)
                                                ->schema([
                                                    TextEntry::make('address')
                                                        ->columnSpan(2)
                                                    ,
                                                    TextEntry::make('city'),
                                                    // TextEntry::make('post_code'),
                                                    // TextEntry::make('phone'),
                                                    // TextEntry::make('fax'),
                                                ])
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
                                                ->formatStateUsing(fn($state) => Warehouse::STATUS_LABELS[$state] ?? '-')
                                                ->badge()
                                                ->color(fn(bool $state) => $state == Warehouse::STATUS_ACTIVE ? 'success' : 'danger')
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
                                            TextEntry::make('companies.alias')
                                                ->label(fn($record) => 'Companies (' . ($record->companies?->count() ?? 0) . ')')
                                                ->badge()
                                                ->placeholder('-')
                                                ->columnSpanFull()
                                            ,
                                            TextEntry::make('projects.name')
                                                ->label(fn($record) => 'Projects (' . ($record->projects?->count() ?? 0) . ')')
                                                ->badge()
                                                ->placeholder('-')
                                                ->columnSpanFull()
                                            ,

                                            ImageEntry::make('users.avatar_url')
                                                ->label(fn($record) => 'Users (' . ($record->users?->count() ?? 0) . ')')
                                                ->limitedRemainingText()
                                                ->circular()
                                                ->stacked()
                                                ->disk('public')
                                                ->defaultImageUrl(fn($record) => $record->users?->count() ? url('avatars/ic_default_user.png') : false)
                                                ->extraImgAttributes([
                                                    'alt' => 'Image',
                                                    'loading' => 'lazy',
                                                ])
                                                ->columnSpanFull()
                                            ,
                                            TextEntry::make('users.name')
                                                ->hiddenLabel()
                                                ->badge()
                                                ->placeholder('-')
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
