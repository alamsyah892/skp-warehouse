<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\Warehouse;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
// use Filament\Forms\Components\Repeater\TableColumn;
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

class WarehouseForm
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
                                    Fieldset::make('Warehouse')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('code')
                                                ->unique(ignoreRecord: true)
                                                ->required()
                                                ->maxLength(8)
                                                ->placeholder('Input Warehouse Code')
                                                ->helperText('Example: BDG, 123, AB-01')
                                            ,
                                            TextInput::make('name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Input Warehouse Name')
                                                ->helperText('Example: BANDUNG RAYA, CIMAHI, CIMAREME')
                                            ,
                                            Textarea::make('description')
                                                ->columnSpanFull()
                                            ,
                                        ])
                                    ,

                                    Fieldset::make('Warehouse Addresses')
                                        ->columnSpanFull()
                                        ->columns(1)
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
                                                            Gunakan bagian ini untuk mengatur Alamat dari <strong>Warehouse</strong><br>
                                                            Alamat dapat digunakan untuk menambahkan info pengiriman pada <strong>Pengajuan / PO</strong>
                                                            '
                                                        ))
                                                        ->color('gray')
                                                        ->columnSpanFull()
                                                    ,
                                                ])
                                            ,

                                            Repeater::make('addresses')
                                                ->hiddenLabel()
                                                ->relationship()
                                                // ->table([
                                                //     TableColumn::make('Address'),
                                                //     TableColumn::make('City'),
                                                // ])
                                                // ->compact()
                                                ->columns(3)
                                                ->schema([
                                                    Textarea::make('address')
                                                        ->required()
                                                        ->helperText('Example: JL PURNAWARAN NO 47 - TAMANSARI')
                                                        ->columnSpan(2)
                                                    ,
                                                    TextInput::make('city')
                                                        ->required()
                                                        ->helperText('Example: BANDUNG')
                                                    ,
                                                    // TextInput::make('post_code')->required(),
                                                    // TextInput::make('phone')->required(),
                                                    // TextInput::make('fax')->required(),
                                                ])
                                                ->collapsed()
                                                ->itemLabel(fn(array $state): ?string => $state['address'] . " - " . $state['city'] ?? null)
                                                ->live()
                                                ->deleteAction(
                                                    fn(Action $action) => $action->requiresConfirmation(),
                                                )
                                                ->addActionLabel('Add new warehouse address')
                                                ->minItems(1)
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
                                                            Gunakan bagian ini untuk mengaitkan <strong>Warehouse</strong> dengan <strong>Perusahaan / Project</strong><br>
                                                            Relasi ini membantu pengelompokan dan pembatasan data di sistem.
                                                            <br>Misalnya dalam <strong>Opsi Warehouse</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Invoice</strong> atau <strong>Laporan</strong>
                                                            '
                                                        ))
                                                        ->color('gray')
                                                        ->columnSpanFull()
                                                    ,
                                                ])
                                            ,

                                            Select::make('companies')
                                                ->relationship('companies', 'alias', )
                                                ->multiple()
                                                ->searchable()
                                                ->preload()
                                                ->columnSpanFull()
                                            ,
                                            Select::make('projects')
                                                ->relationship('projects', 'name', )
                                                ->multiple()
                                                ->searchable()
                                                ->preload()
                                                ->columnSpanFull()
                                            ,

                                            Select::make('users')
                                                ->relationship('users', 'name', )
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
                                                    ? Warehouse::STATUS_LABELS[Warehouse::STATUS_ACTIVE]
                                                    : Warehouse::STATUS_LABELS[Warehouse::STATUS_INACTIVE]
                                                )
                                                ->default(true)
                                                ->live()
                                                ->helperText(
                                                    fn($get) => new HtmlString(
                                                        $get('is_active')
                                                        ? '✅ Warehouse ini akan ditampilkan dalam <strong>Opsi Warehouse</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Invoice</strong>'
                                                        : '⚠️ Warehouse ini tidak akan ditampilkan dalam <strong>Opsi Warehouse</strong> pada <strong>Form Pembuatan Pengajuan / PO / Penerimaan / Invoice</strong>'
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
