<?php

namespace App\Filament\Resources\Vendors\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\ItemCategory;
use App\Models\Vendor;
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

class VendorForm
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
                                    Fieldset::make('Vendor')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('code')
                                                ->unique(ignoreRecord: true)
                                                ->maxLength(8)
                                                ->placeholder('Input Vendor Code (Optional)')
                                                ->helperText('Example: ABC, 123, AB-01')
                                            ,
                                            TextInput::make('name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Input Vendor Name')
                                                ->helperText('Example: MITRA SARANA SEJAHTERA, CV; AKAN SELALU ABADI, PT; MENCARI CINTA SEJATI, PT')
                                            ,
                                            Textarea::make('description')
                                                ->columnSpanFull()
                                            ,

                                            Textarea::make('address')
                                                ->required()
                                                ->placeholder('Input Vendor Address')
                                                ->helperText('Example: JL ASIA AFRIKA NO 1234 - LENGKONG')
                                                ->columnSpanFull()
                                            ,
                                            TextInput::make('city')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Input Vendor City')
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
                                            Section::make()
                                                ->columnSpanFull()
                                                ->schema([
                                                    Placeholder::make('info')
                                                        ->hiddenLabel()
                                                        ->icon(Heroicon::InformationCircle)
                                                        ->iconColor('info')
                                                        ->content(new HtmlString(
                                                            '
                                                            <strong> Info</strong><br>
                                                            Kategori Item (Domain) menentukan jenis barang atau layanan yang disediakan oleh Vendor ini.<br>
                                                            Relasi ini membantu pengelompokan dan pembatasan data di sistem.
                                                            <br>Misalnya dalam <strong>Opsi Vendor</strong> pada <strong>Form Pembuatan PO / Invoice</strong> atau <strong>Laporan</strong>.
                                                            '
                                                        ))
                                                        ->color('gray')
                                                        ->columnSpanFull()
                                                    ,
                                                ])
                                            ,

                                            Select::make('itemCategories')
                                                ->label('Item categories (Domain)')
                                                ->relationship(
                                                    'itemCategories',
                                                    'name',
                                                    modifyQueryUsing: fn($query) =>
                                                    $query
                                                        ->where('level', ItemCategory::LEVEL_DOMAIN)
                                                        ->where('allow_po', true)
                                                )
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
                                                    ? Vendor::STATUS_LABELS[Vendor::STATUS_ACTIVE]
                                                    : Vendor::STATUS_LABELS[Vendor::STATUS_INACTIVE]
                                                )
                                                ->default(true)
                                                ->live()
                                                ->helperText(
                                                    fn($get) => new HtmlString(
                                                        $get('is_active')
                                                        ? '✅ Vendor ini akan ditampilkan dalam <strong>Opsi Vendor</strong> pada <strong>Form Pembuatan PO / Invoice</strong>'
                                                        : '⚠️ Vendor ini tidak akan ditampilkan dalam <strong>Opsi Vendor</strong> pada <strong>Form Pembuatan PO / Invoice</strong>'
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