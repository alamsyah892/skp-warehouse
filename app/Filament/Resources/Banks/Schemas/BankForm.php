<?php

namespace App\Filament\Resources\Banks\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\Bank;
use App\Models\Company;
use App\Models\Currency;
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

class BankForm
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
                                    Fieldset::make('Bank')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            Select::make('company_id')
                                                ->relationship('company', 'alias')
                                                ->required()
                                                ->native(false)
                                                ->reactive()
                                                ->helperText(function ($get) {
                                                    $company = $get('company_id');

                                                    if (blank($company)) {
                                                        return '';
                                                    }

                                                    return Company::find($company)?->name;
                                                })
                                                ->columnSpanFull()
                                            ,

                                            TextInput::make('code')
                                                ->unique(ignoreRecord: true)
                                                ->required()
                                                ->maxLength(8)
                                                ->placeholder('Input Bank Code')
                                                ->helperText('Example: ABC, 123, AB-01')
                                            ,
                                            TextInput::make('name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Input Bank Name')
                                                ->helperText('Example: BCA, MANDIRI, KAS')
                                            ,
                                            Textarea::make('description')
                                                ->columnSpanFull()
                                            ,

                                            TextInput::make('account_number')
                                                ->maxLength(255)
                                            ,
                                            Select::make('currency_id')
                                                ->relationship('currency', 'code')
                                                ->required()
                                                ->native(false)
                                                ->reactive()
                                                ->helperText(function ($get) {
                                                    $currency = $get('currency_id');

                                                    if (blank($currency)) {
                                                        return '';
                                                    }

                                                    return Currency::find($currency)?->name;
                                                })
                                            ,
                                            TextInput::make('balance')
                                                ->numeric()
                                                ->disabled()
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
                                                    ? Bank::STATUS_LABELS[Bank::STATUS_ACTIVE]
                                                    : Bank::STATUS_LABELS[Bank::STATUS_INACTIVE]
                                                )
                                                ->default(true)
                                                ->live()
                                                ->helperText(
                                                    fn($get) => new HtmlString(
                                                        $get('is_active')
                                                        ? '✅ Bank ini akan ditampilkan dalam <strong>Opsi Bank</strong> pada <strong>Form Pembuatan Invoice</strong>'
                                                        : '⚠️ Bank ini tidak akan ditampilkan dalam <strong>Opsi Bank</strong> pada <strong>Form Pembuatan Invoice</strong>'
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
