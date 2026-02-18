<?php

namespace App\Filament\Resources\ItemCategories\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\ItemCategory;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;

class ItemCategoryInfolist
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
                                    Fieldset::make('Item Category')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('level')
                                                ->badge()
                                                ->formatStateUsing(fn(int|null $state) => ItemCategory::LEVEL_LABELS[$state] ?? '-')
                                                ->color(fn(int|null $state) => ItemCategory::LEVEL_COLOR[$state] ?? 'default')
                                                ->columnSpanFull()
                                            ,
                                            TextEntry::make('parent_path')
                                                ->label(function ($record) {
                                                    $level = $record->parent?->level;

                                                    return ($level && isset(ItemCategory::LEVEL_LABELS[$level])) ? ItemCategory::LEVEL_LABELS[$level] : 'Parent';
                                                })
                                                ->placeholder('-')
                                                ->columnSpanFull()
                                            ,

                                            TextEntry::make('code')
                                                ->badge()
                                                ->color('info')
                                                ->placeholder('-')
                                                ->fontFamily(FontFamily::Mono)
                                                ->size(TextSize::Large)
                                            ,
                                            TextEntry::make('name')
                                                ->label(fn($get) => ItemCategory::LEVEL_LABELS[$get('level')] . ' Name' ?? 'Name')
                                            ,
                                            TextEntry::make('description')
                                                ->placeholder('-')
                                                ->columnSpanFull()
                                            ,
                                        ])
                                    ,

                                    Fieldset::make('Item')
                                        ->columnSpanFull()
                                        ->columns(1)
                                        ->schema([
                                            RepeatableEntry::make('items')
                                                ->label(fn($record) => 'Items (' . ($record->items?->count() ?? 0) . ')')
                                                ->table([
                                                    TableColumn::make('Item Code'),
                                                    TableColumn::make('Item Name'),
                                                ])
                                                ->schema([
                                                    TextEntry::make('code')
                                                        ->fontFamily(FontFamily::Mono)
                                                    ,
                                                    TextEntry::make('name'),
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
                                            TextEntry::make('allow_po')
                                                ->label('Allow PO')
                                                ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                                                ->color(fn(bool $state) => $state ? 'success' : 'danger')
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

