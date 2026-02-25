<?php

namespace App\Filament\Resources\Vendors\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\Vendor;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class VendorInfolist
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
        return Section::make('Vendor Information')
            ->icon(Heroicon::BuildingStorefront)
            ->iconColor('primary')
            ->description('Informasi utama dan identitas dasar Vendor.')
            // ->afterHeader([
            //     Action::make('edit')
            //         ->label('Edit')
            //         ->icon(Heroicon::PencilSquare)
            //         ->url(fn($record) => VendorResource::getUrl('edit', ['record' => $record]))
            //     ,
            // ])
            ->collapsible()
            ->columnSpanFull()
            ->columns(3)
            ->compact()
            ->schema([
                TextEntry::make('name')
                    ->columnSpan(2)
                    ->icon(fn($record) => $record->is_active ? Heroicon::CheckBadge : Heroicon::ExclamationTriangle)
                    ->iconPosition(IconPosition::After)
                    ->iconColor(fn($record) => $record->is_active ? 'success' : 'warning')
                    ->size(TextSize::Large)
                    ->weight(FontWeight::Bold)
                ,

                TextEntry::make('code')
                    ->badge()
                    ->fontFamily(FontFamily::Mono)
                    ->size(TextSize::Large)
                    ->placeholder('-')
                ,

                Grid::make()
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('-')
                        ,

                        TextEntry::make('address')
                            ->columnSpanFull()
                        ,

                        TextEntry::make('city')
                        ,
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
                    ])
                ,
                Grid::make()
                    ->columns(1)
                    ->schema([
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
        ;
    }

    protected static function tabSection(): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                Tab::make('PO History')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->badge(null)
                    ->schema([
                        Callout::make()
                            ->description('Riwayat semua Purchase Order yang terkait dengan Vendor ini.')
                            ->info()
                            ->color(null)
                        ,
                    ])
                ,

                Tab::make('Item History')
                    ->icon(Heroicon::OutlinedCube)
                    ->badge(null)
                    ->schema([
                        Callout::make()
                            ->description('Riwayat semua Item yang terkait dengan Vendor ini.')
                            ->info()
                            ->color(null)
                        ,
                    ])
                ,

                ActivityLogTab::make('Activity Logs'),
            ])
        ;
    }

    protected static function otherInfoSection(): Section
    {
        return Section::make('Other Information')
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->description('Informasi lain terkait Vendor.')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                TextEntry::make('is_active')
                    ->label('Status')
                    ->icon(fn($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle)
                    ->formatStateUsing(fn($state) => Vendor::STATUS_LABELS[$state])
                    ->badge()
                    ->color(fn($state) => $state == Vendor::STATUS_ACTIVE ? 'success' : 'warning')
                    ->columnSpanFull()
                ,

                TextEntry::make('created_at')->date()
                    ->color('gray')
                    ->size(TextSize::Small)
                ,
                TextEntry::make('updated_at')->date()
                    ->color('gray')
                    ->size(TextSize::Small)
                ,
                TextEntry::make('deleted_at')->date()
                    ->color('gray')
                    ->size(TextSize::Small)
                    ->visible(fn($state) => $state != null)
                ,
            ])
        ;
    }

    protected static function relatedDataSection(): Section
    {
        return Section::make('Related Data')
            ->icon(Heroicon::Link)
            ->iconColor('primary')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                Callout::make()
                    ->columnSpanFull()
                    ->description('Daftar entitas yang terhubung dengan Vendor ini.')
                    ->info()
                    ->color(null)
                ,

                TextEntry::make('itemCategories.name')
                    ->label(fn($record) => "Item Categories (Domain) ({$record->item_categories_count})")
                    ->badge()
                    ->placeholder('-')
                    ->columnSpanFull()
                ,
            ])
        ;
    }
}
