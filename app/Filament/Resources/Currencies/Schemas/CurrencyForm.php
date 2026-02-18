<?php

namespace App\Filament\Resources\Currencies\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\Currency;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CurrencyForm
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
                                    Fieldset::make('Currency')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('code')
                                                ->unique(ignoreRecord: true)
                                                ->required()
                                                ->maxLength(8)
                                                ->placeholder('Input Currency Code')
                                                ->helperText('Example: IDR, USD')
                                            ,
                                            TextInput::make('name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Input Currency Name')
                                                ->helperText('Example: Rupiah, US Dollar')
                                            ,
                                            Textarea::make('description')
                                                ->columnSpanFull()
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
                                                    ? Currency::STATUS_LABELS[Currency::STATUS_ACTIVE]
                                                    : Currency::STATUS_LABELS[Currency::STATUS_INACTIVE]
                                                )
                                                ->default(true)
                                                ->live()
                                                ->helperText(
                                                    fn($get) => new HtmlString(
                                                        $get('is_active')
                                                        ? '✅ Mata uang ini akan ditampilkan dalam <strong>Opsi Mata uang</strong> pada <strong>Form Pembuatan PO / Invoice</strong>'
                                                        : '⚠️ Mata uang ini tidak akan ditampilkan dalam <strong>Opsi Mata uang</strong> pada <strong>Form Pembuatan PO / Invoice</strong>'
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
