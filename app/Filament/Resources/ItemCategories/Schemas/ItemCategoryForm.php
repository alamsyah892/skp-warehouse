<?php

namespace App\Filament\Resources\ItemCategories\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\ItemCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ItemCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->tabs([
                Tab::make('Form')->schema([
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
                                            Select::make('level')
                                                ->options(ItemCategory::LEVEL_LABELS)
                                                ->required()
                                                ->disabled(fn(?ItemCategory $record) => $record?->children()->exists())
                                                ->reactive()
                                                ->afterStateUpdated(function ($set) {
                                                    $set('domain_id', null);
                                                    $set('category_id', null);
                                                })
                                                ->native(false)
                                                ->helperText(new HtmlString(
                                                    '
                                                    Menentukan level kategori item.
                                                    <ul>
                                                    <li><strong>Domain</strong>: kategori utama (misal: Konstruksi, ATK, IT Supply)</li>
                                                    <li><strong>Category</strong>: turunan Domain (misal: Semen, Kabel, Kertas, Hardware)</li>
                                                    <li><strong>Sub Category</strong>: turunan Category (misal: Mortar, Semen Putih, HVS A4, Memory)</li>
                                                    </ul>
                                                    '
                                                ))
                                                ->columnSpanFull()
                                            ,

                                            /* ================= DOMAIN ================= */
                                            Select::make('domain_id')
                                                ->label('Domain')
                                                ->options(ItemCategory::where('level', ItemCategory::LEVEL_DOMAIN)->pluck('name', 'id'))
                                                ->required(fn($get) => (int) $get('level') >= ItemCategory::LEVEL_CATEGORY)
                                                ->visible(fn($get) => (int) $get('level') >= ItemCategory::LEVEL_CATEGORY)
                                                // ->hidden(fn($get) => (int) $get('level') < ItemCategory::LEVEL_CATEGORY)
                                                ->reactive()
                                                ->dehydrated(true)
                                                ->dehydratedWhenHidden(true)
                                                ->afterStateUpdated(fn($set) => $set('category_id', null))
                                                ->native(false)
                                                ->columnSpanFull()
                                            ,

                                            /* ================= CATEGORY ================= */
                                            Select::make('category_id')
                                                ->label('Category')
                                                ->options(
                                                    fn($get) =>
                                                    ItemCategory::where('parent_id', $get('domain_id'))
                                                        ->where('level', ItemCategory::LEVEL_CATEGORY)
                                                        ->pluck('name', 'id')
                                                )
                                                ->required(fn($get) => (int) $get('level') >= ItemCategory::LEVEL_SUB_CATEGORY)
                                                ->visible(fn($get) => (int) $get('level') >= ItemCategory::LEVEL_SUB_CATEGORY)
                                                // ->hidden(fn($get) => (int) $get('level') < ItemCategory::LEVEL_SUB_CATEGORY)
                                                ->reactive()
                                                ->dehydrated(true)
                                                ->dehydratedWhenHidden(true)
                                                ->afterStateUpdated(fn($set) => $set('sub_category_id', null))
                                                ->native(false)
                                                ->columnSpanFull()
                                            ,

                                            /* ================= SUB CATEGORY ================= */
                                            // Select::make('sub_category_id')
                                            //     ->label('Sub Category')
                                            //     ->options(
                                            //         fn($get) =>
                                            //         ItemCategory::where('parent_id', $get('category_id'))
                                            //             ->where('level', ItemCategory::LEVEL_SUB_CATEGORY)
                                            //             ->pluck('name', 'id')
                                            //     )
                                            //     ->hidden(fn($get) => (int) $get('level') < ItemCategory::LEVEL_FINAL_CATEGORY)
                                            //     ->required(fn($get) => (int) $get('level') >= ItemCategory::LEVEL_FINAL_CATEGORY)
                                            //     ->reactive()
                                            //     ->columnSpanFull()
                                            // ,

                                            /* ================= DATA ================= */
                                            TextInput::make('code')
                                                ->unique(ignoreRecord: true)
                                                // ->required()
                                                ->maxLength(8)
                                                ->placeholder(fn($get) => 'Input ' . ItemCategory::LEVEL_LABELS[$get('level')] . ' Code (Optional)' ?? 'Input Item Category Code (Optional)')
                                                ->helperText('Example: ABC, 123, AB-01')
                                                ->hidden(fn($get) => blank($get('level')))
                                            ,
                                            TextInput::make('name')
                                                ->label(fn($get) => ItemCategory::LEVEL_LABELS[$get('level')] . ' Name' ?? 'Name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder(fn($get) => 'Input ' . ItemCategory::LEVEL_LABELS[$get('level')] . ' Name' ?? 'Input Name')
                                                ->helperText(fn($get) => match ((int) $get('level')) {
                                                    ItemCategory::LEVEL_DOMAIN => 'Example: Konstruksi, ATK, IT Supply',
                                                    ItemCategory::LEVEL_CATEGORY => 'Example: Semen, Kabel, Kertas, Hardware',
                                                    ItemCategory::LEVEL_SUB_CATEGORY => 'Example: Mortar, Semen Putih, HVS A4, Memory',
                                                    // ItemCategory::LEVEL_FINAL_CATEGORY => 'Example: Semen Portland, Cat Interior',
                                                    default => 'Example: Konstruksi, ATK, IT Supply',
                                                })
                                                ->hidden(fn($get) => blank($get('level')))
                                            ,
                                            Textarea::make('description')
                                                ->columnSpanFull()
                                                ->hidden(fn($get) => blank($get('level')))
                                            ,
                                        ])
                                    ,
                                ])
                            ,
                            Section::make() // right
                                ->contained(false)
                                ->schema([
                                    Fieldset::make('Configuration')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            Toggle::make('allow_po')
                                                ->label('Allow PO')
                                                ->label(
                                                    fn($get) => $get('allow_po')
                                                    ? 'Allow PO'
                                                    : 'Block PO'
                                                )
                                                ->default(true)
                                                ->live()
                                                ->helperText(
                                                    fn($get) => new HtmlString(
                                                        $get('allow_po')
                                                        ? '✅ Item yang termasuk ke dalam Kategori ini akan ditampilkan dalam <strong>Opsi Item</strong> pada <strong>Form Pembuatan Pengajuan</strong>'
                                                        : '⚠️ Item yang termasuk ke dalam Kategori ini tidak akan ditampilkan dalam <strong>Opsi Item</strong> pada <strong>Form Pembuatan Pengajuan</strong>'
                                                    )
                                                )
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