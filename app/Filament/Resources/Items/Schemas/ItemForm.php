<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\Item;
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

class ItemForm
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
                                    Fieldset::make('Item')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            Select::make('category_id')
                                                ->label('Category')
                                                ->options(
                                                    ItemCategory::query()
                                                        ->whereDoesntHave('children') // leaf only
                                                        ->orderBy('parent_id')
                                                        ->orderBy('name')
                                                        ->get()
                                                        ->groupBy(fn($category) => $category->parent_path)
                                                        ->mapWithKeys(fn($group) => [
                                                            $group->first()->parent_path =>
                                                                $group->pluck('name', 'id')->toArray(),
                                                        ])
                                                        ->toArray()
                                                )
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->reactive()
                                                ->helperText(function ($get) {
                                                    $categoryId = $get('category_id');

                                                    if (blank($categoryId)) {
                                                        // return 'Select a category to see its full path.';
                                                        return '';
                                                    }

                                                    return ItemCategory::find($categoryId)?->parent_full_path;
                                                })
                                                ->columnSpanFull()
                                            ,

                                            TextInput::make('code')
                                                ->unique(ignoreRecord: true)
                                                ->required()
                                                ->maxLength(8)
                                                ->placeholder('Input Item Code')
                                                ->helperText('Example: ABC, 123, AB-01')
                                            ,
                                            TextInput::make('name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Input Item Name')
                                                ->helperText('Example: MORTAR MU 270 @25 KG, SIDU A4 8G, Corsair DDR4 3200')
                                            ,
                                            Textarea::make('description')
                                                ->columnSpanFull()
                                            ,

                                            TextInput::make('unit')
                                                ->required()
                                                ->maxLength(16)
                                                ->placeholder('Input Item Unit')
                                                ->helperText('Example: PCS, M2, KG')
                                            ,
                                            Select::make('type')
                                                ->options(Item::TYPE_LABELS)
                                                ->native(false)
                                                ->required()
                                                ->helperText(new HtmlString(
                                                    '
                                                    <ul>
                                                        <li><b>Stock Item</b>: Item yang memiliki stok dan dapat digunakan berulang.</li>
                                                        <li><b>Consumable / Once Use</b>: Item yang habis sekali pakai dan tidak disimpan sebagai stok.</li>
                                                    </ul>
                                                    '
                                                ))
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
                                            Toggle::make('is_active')
                                                ->label(
                                                    fn($get) => $get('is_active')
                                                    ? Item::STATUS_LABELS[Item::STATUS_ACTIVE]
                                                    : Item::STATUS_LABELS[Item::STATUS_INACTIVE]
                                                )
                                                ->default(true)
                                                ->live()
                                                ->helperText(
                                                    fn($get) => new HtmlString(
                                                        $get('is_active')
                                                        ? '✅ Item ini akan ditampilkan dalam <strong>Opsi Item</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Pengeluaran / Invoice</strong>'
                                                        : '⚠️ Item ini tidak akan ditampilkan dalam <strong>Opsi Item</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Pengeluaran / Invoice</strong>'
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