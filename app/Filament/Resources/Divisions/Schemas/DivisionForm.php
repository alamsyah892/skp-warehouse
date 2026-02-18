<?php

namespace App\Filament\Resources\Divisions\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\Division;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class DivisionForm
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
                                    Fieldset::make('Division')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('code')
                                                ->unique(ignoreRecord: true)
                                                ->required()
                                                ->maxLength(8)
                                                ->placeholder('Input Division Code')
                                                ->helperText('Example: HV, 123, AB-01')
                                            ,
                                            TextInput::make('name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Input Division Name')
                                                ->helperText('Example: HVAC, INTERIOR, ME, SIPIL')
                                            ,
                                            Textarea::make('description')
                                                ->columnSpanFull()
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
                                                            Gunakan bagian ini untuk mengaitkan <strong>Divisi</strong> dengan <strong>Perusahaan</strong><br>
                                                            Relasi ini membantu pengelompokan dan pembatasan data di sistem.
                                                            <br>Misalnya dalam <strong>Opsi Divisi</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Invoice</strong> atau <strong>Laporan</strong>
                                                            '
                                                        ))
                                                        ->color('gray')
                                                        ->columnSpanFull()
                                                    ,
                                                ])
                                            ,

                                            Select::make('companies')
                                                ->relationship('companies', 'alias')
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
                                                    ? Division::STATUS_LABELS[Division::STATUS_ACTIVE]
                                                    : Division::STATUS_LABELS[Division::STATUS_INACTIVE]
                                                )
                                                ->default(true)
                                                ->live()
                                                ->helperText(
                                                    fn($get) => new HtmlString(
                                                        $get('is_active')
                                                        ? '✅ Divisi ini akan ditampilkan dalam <strong>Opsi Divisi</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Invoice</strong>'
                                                        : '⚠️ Divisi ini tidak akan ditampilkan dalam <strong>Opsi Divisi</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Invoice</strong>'
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
