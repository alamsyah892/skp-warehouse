<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\Company;
use Filament\Forms\Components\Placeholder;
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
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class CompanyForm
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
                                    Fieldset::make('Company')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('code')
                                                ->unique(ignoreRecord: true)
                                                ->required()
                                                ->maxLength(8)
                                                ->placeholder('Input Company Code')
                                                ->helperText('Example: SKP, 123, AB-01')
                                            ,
                                            TextInput::make('alias')
                                                ->label('Business name / alias')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Input Company Business name / alias')
                                                ->helperText('Shortname, example: SKP, SANBE, CAPRI')
                                            ,
                                            TextInput::make('name')
                                                ->label('Company name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Input Company Name')
                                                ->helperText('Example: SANBE KARYAPERSADA, PT; SANBE FARMA, PT; CAPRIFARMINDO, PT')
                                            ,
                                            Textarea::make('description')
                                                ->columnSpanFull()
                                            ,

                                            Textarea::make('address')
                                                ->required()
                                                ->placeholder('Input Company Address')
                                                ->helperText('Example: JL PURNAWARAN NO 47 - TAMANSARI')
                                                ->columnSpanFull()
                                            ,
                                            TextInput::make('city')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Input Company City')
                                                ->helperText('Example: BANDUNG')
                                            ,
                                            TextInput::make('post_code')
                                                ->maxLength(255)
                                            ,
                                            TextInput::make('contact_person')
                                                ->maxLength(255)
                                            ,
                                            TextInput::make('contact_person_position')
                                                ->maxLength(255)
                                            ,
                                            TextInput::make('phone')
                                                ->tel()
                                                ->maxLength(255)
                                            ,
                                            TextInput::make('fax')
                                                ->tel()
                                                ->maxLength(255)
                                            ,
                                            TextInput::make('email')
                                                ->label('Email address')
                                                ->email()
                                                ->maxLength(255)
                                            ,
                                            TextInput::make('website')
                                                ->url()
                                                ->maxLength(255)
                                            ,
                                            TextInput::make('tax_number')
                                                ->maxLength(255)
                                            ,
                                        ])
                                    ,

                                    Fieldset::make('Related Data')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            Section::make() // left
                                                ->columnSpanFull()
                                                ->schema([
                                                    Placeholder::make('info')
                                                        ->hiddenLabel()
                                                        ->icon(Heroicon::InformationCircle)
                                                        ->iconColor('info')
                                                        ->content(new HtmlString(
                                                            '
                                                            <strong> Info</strong><br>
                                                            Gunakan bagian ini untuk mengaitkan <strong>Perusahaan</strong> dengan <strong>Warehouse / Divisi / Project</strong><br>
                                                            Relasi ini membantu pengelompokan dan pembatasan data di sistem.
                                                            <br>Misalnya dalam <strong>Opsi Perusahaan</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Invoice</strong> atau <strong>Laporan</strong>
                                                            '
                                                        ))
                                                        ->color('gray')
                                                        ->columnSpanFull()
                                                    ,
                                                ])
                                            ,

                                            Select::make('warehouses')
                                                ->relationship('warehouses', 'name')
                                                ->multiple()
                                                ->searchable()
                                                ->preload()
                                                ->columnSpanFull()
                                            ,
                                            Select::make('divisions')
                                                ->relationship('divisions', 'name')
                                                ->multiple()
                                                ->searchable()
                                                ->preload()
                                                ->columnSpanFull()
                                            ,
                                            Select::make('projects')
                                                ->relationship('projects', 'name')
                                                ->multiple()
                                                ->searchable()
                                                ->preload()
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
                                                    ? Company::STATUS_LABELS[Company::STATUS_ACTIVE]
                                                    : Company::STATUS_LABELS[Company::STATUS_INACTIVE]
                                                )
                                                ->default(true)
                                                ->live()
                                                ->helperText(
                                                    fn($get) => new HtmlString(
                                                        $get('is_active')
                                                        ? '✅ Perusahaan ini akan ditampilkan dalam <strong>Opsi Perusahaan</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Invoice</strong>'
                                                        : '⚠️ Perusahaan ini tidak akan ditampilkan dalam <strong>Opsi Perusahaan</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Invoice</strong>'
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
