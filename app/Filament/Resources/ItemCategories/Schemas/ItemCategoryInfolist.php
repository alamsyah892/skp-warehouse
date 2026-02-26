<?php

namespace App\Filament\Resources\ItemCategories\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Filament\Resources\Items\ItemResource;
use App\Models\ItemCategory;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmptyState;
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

class ItemCategoryInfolist
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
        return Section::make('Item Category Information')
            ->icon(Heroicon::Swatch)
            ->iconColor('primary')
            ->description('Informasi utama dan identitas dasar Kategori Item.')
            // ->afterHeader([
            //     Action::make('edit')
            //         ->label('Edit')
            //         ->icon(Heroicon::PencilSquare)
            //         ->url(fn($record) => ItemCategoryResource::getUrl('edit', ['record' => $record]))
            //     ,
            // ])
            ->collapsible()
            ->columnSpanFull()
            ->columns(3)
            ->compact()
            ->schema([
                TextEntry::make('name')
                    ->label(function ($record) {
                        $level = $record->level;

                        return ($level && isset(ItemCategory::LEVEL_LABELS[$level])) ? ItemCategory::LEVEL_LABELS[$level] . ' Name' : 'Name';
                    })
                    ->weight(FontWeight::Bold)
                    ->size(TextSize::Large)
                    ->columnSpan(2)
                ,
                TextEntry::make('code')
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->icon(Heroicon::Hashtag)
                    ->iconColor('primary')
                    ->placeholder('-')
                    ->badge()
                ,
                Grid::make()
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('level')
                            ->formatStateUsing(fn(int|null $state) => ItemCategory::LEVEL_LABELS[$state] ?? '-')
                            ->color(fn(int|null $state) => ItemCategory::LEVEL_COLOR[$state] ?? 'default')
                            ->badge()
                            ->size(TextSize::Large)
                        ,
                        TextEntry::make('parent_path')
                            ->label(function ($record) {
                                $level = $record->parent?->level;

                                return ($level && isset(ItemCategory::LEVEL_LABELS[$level])) ? ItemCategory::LEVEL_LABELS[$level] : 'Parent';
                            })
                            ->placeholder('-')
                        ,

                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('-')
                        ,
                    ])
                ,
                Grid::make()
                    ->columns(1)
                    ->schema([
                        // TextEntry::make('name'),
                        // TextEntry::make('code'),
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
                Tab::make('Items')
                    ->icon(Heroicon::OutlinedCube)
                    ->badge(fn($record) => $record->items_count ?: null)
                    ->schema([
                        Callout::make()
                            ->description('Item yang terkait dengan Kategori Item ini.')
                            ->info()
                            ->color(null)
                            ->columnSpanFull()
                        ,

                        RepeatableEntry::make('items')
                            ->table([
                                TableColumn::make('Item Name'),
                                TableColumn::make('Item Code'),
                            ])
                            ->schema([
                                TextEntry::make('name')
                                    ->url(
                                        fn($record) => ItemResource::getUrl('view', [
                                            'record' => $record->id,
                                        ])
                                    )
                                    ->openUrlInNewTab() // optional
                                    ->color('primary')
                                    ->icon(Heroicon::ArrowTopRightOnSquare)
                                    ->iconPosition(IconPosition::After)
                                ,
                                TextEntry::make('code')
                                    ->fontFamily(FontFamily::Mono)
                                    ->icon(Heroicon::Hashtag)
                                    ->iconColor('primary')
                                    ->badge()
                                    ->wrap(false)
                                ,
                            ])
                            ->visible(fn($record) => $record->items_count > 0)
                        ,
                        EmptyState::make('No item yet')
                            ->description('No item has been recorded yet.')
                            ->icon(Heroicon::OutlinedCube)
                            ->visible(fn($record) => $record->items_count == 0)
                            ->contained(false)
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
            ->description('Informasi lain terkait Kategori Item.')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                TextEntry::make('allow_po')
                    ->label('Allow PO')
                    ->formatStateUsing(fn($state) => $state ? 'Allowed' : 'Blocked')
                    ->icon(fn($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle)
                    ->color(fn($state) => $state ? 'success' : 'danger')
                    ->badge()
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

    protected static function relatedDataSection(): Section|string
    {
        return '';
        return Section::make('Related Data')
            ->icon(Heroicon::Link)
            ->iconColor('primary')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                Callout::make()
                    ->description('Daftar entitas yang terhubung dengan Kategori Item ini.')
                    ->info()
                    ->color(null)
                    ->columnSpanFull()
                ,
            ])
        ;
    }
}

