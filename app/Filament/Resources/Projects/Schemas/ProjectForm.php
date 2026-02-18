<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\Project;
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

class ProjectForm
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
                                    Fieldset::make('Project')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('code')
                                                ->unique(ignoreRecord: true)
                                                ->required()
                                                ->maxLength(8)
                                                ->placeholder('Input Project Code')
                                                ->helperText('Example: ABC, 123, AB-01')
                                            ,
                                            TextInput::make('po_code')
                                                ->label('Code for PO')
                                                ->unique(ignoreRecord: true)
                                                ->required()
                                                ->maxLength(8)
                                                ->placeholder('Input Project Code for PO')
                                                ->helperText('Example: ABC, 123, ABC-123')
                                            ,
                                            TextInput::make('name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Input Project Name')
                                                ->helperText('Example: CENTRAL WAREHOUSE, TAMANSARI 10, ABADI')
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
                                                            Gunakan bagian ini untuk mengaitkan <strong>Project</strong> dengan <strong>Perusahaan / Warehouse</strong><br>
                                                            Relasi ini membantu pengelompokan dan pembatasan data di sistem.
                                                            <br>Misalnya dalam <strong>Opsi Project</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Invoice</strong> atau <strong>Laporan</strong>
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
                                            Select::make('warehouses')
                                                ->relationship('warehouses', 'name')
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
                                                        ? '✅ Project ini akan ditampilkan dalam <strong>Opsi Project</strong> pada <strong>Form Pembuatan Pengajuan</strong>'
                                                        : '⚠️ Project ini tidak akan ditampilkan dalam <strong>Opsi Project</strong> pada <strong>Form Pembuatan Pengajuan</strong>'
                                                    )
                                                )
                                                ->columnSpanFull()
                                            ,

                                            Toggle::make('is_active')
                                                ->label(
                                                    fn($get) => $get('is_active')
                                                    ? Project::STATUS_LABELS[Project::STATUS_ACTIVE]
                                                    : Project::STATUS_LABELS[Project::STATUS_INACTIVE]
                                                )
                                                ->default(true)
                                                ->live()
                                                ->helperText(
                                                    fn($get) => new HtmlString(
                                                        $get('is_active')
                                                        ? '✅ Project ini akan ditampilkan dalam <strong>Opsi Project</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Invoice</strong>'
                                                        : '⚠️ Project ini tidak akan ditampilkan dalam <strong>Opsi Project</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Invoice</strong>'
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
