<?php

namespace App\Filament\Resources\Banks\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\Bank;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class BankInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    // 'lg' => 1,
                    'xl' => 4,
                    '2xl' => 4,
                ])
                ->schema([
                    Grid::make() // left / 1
                        ->columnSpan([
                            'xl' => 3,
                            '2xl' => 3,
                        ])
                        ->schema([
                            static::dataSection(), // 1.1

                            static::tabSection(), // 1.2
                        ])
                    ,

                    Grid::make() // right / 2
                        ->columnSpan([
                            'xl' => 1,
                            '2xl' => 1,
                        ])
                        ->schema([
                            static::otherInfoSection(), // 2.1

                            // static::relatedDataSection(), // 2.2
                        ])
                    ,
                ])
            ,
        ]);
    }

    protected static function dataSection(): Section
    {
        return Section::make('Bank Information')
            ->icon(Heroicon::BuildingLibrary)
            ->iconColor('primary')
            ->description('Informasi utama dan identitas dasar Bank.')
            // ->afterHeader([
            //     Action::make('edit')
            //         ->label('Edit')
            //         ->icon(Heroicon::PencilSquare)
            //         ->url(fn($record) => BankResource::getUrl('edit', ['record' => $record]))
            //     ,
            // ])
            ->collapsible()
            ->columnSpanFull()
            ->columns(3)
            ->compact()
            ->schema([
                TextEntry::make('name')
                    ->columnSpan(2)
                    ->icon(fn($record) => $record->is_active ? Heroicon::CheckBadge : Heroicon::ExclamationTriangle)
                    ->iconPosition(IconPosition::After)
                    ->iconColor(fn($record) => $record->is_active ? 'success' : 'warning')
                    ->size(TextSize::Large)
                    ->weight(FontWeight::Bold)
                ,

                TextEntry::make('code')
                    ->badge()
                    ->fontFamily(FontFamily::Mono)
                    ->size(TextSize::Large)
                ,

                Grid::make()
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('company')
                            ->formatStateUsing(
                                fn($record) => new HtmlString(
                                    "<strong>{$record->company->alias}</strong></br>{$record->company->name}"
                                )
                            )
                        ,

                        TextEntry::make('account_number')
                            ->placeholder('-')
                        ,
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('-')
                        ,

                        TextEntry::make('currency.code')
                            ->label('Currency')
                            ->badge()
                            ->fontFamily(FontFamily::Mono)
                            ->size(TextSize::Large)
                        ,

                        TextEntry::make('balance')
                            ->money('IDR', decimalPlaces: 2)
                        ,
                    ])
                ,
            ])
        ;
    }

    protected static function tabSection(): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                // Tab::make('PR History')
                //     ->icon(Heroicon::OutlinedClipboardDocumentList)
                //     ->badge(fn($record) => $record->purchase_requests_count ?: null)
                //     ->schema([
                //         Callout::make()
                //             ->description('Riwayat semua Pengajuan Pembelian yang terkait dengan Bank ini.')
                //             ->info()
                //             ->color(null)
                //         ,
                //     ])
                // ,

                ActivityLogTab::make('Activity Logs'),
            ])
        ;
    }

    protected static function otherInfoSection(): Section
    {
        return Section::make('Other Information')
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->description('Informasi lain terkait Bank.')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                TextEntry::make('is_active')
                    ->label('Status')
                    ->icon(fn($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle)
                    ->formatStateUsing(fn($state) => Bank::STATUS_LABELS[$state])
                    ->badge()
                    ->color(fn($state) => $state == Bank::STATUS_ACTIVE ? 'success' : 'warning')
                    ->columnSpanFull()
                ,

                TextEntry::make('created_at')->date()
                    ->color('gray')
                    ->size(TextSize::Small)
                ,
                TextEntry::make('updated_at')->date()
                    ->color('gray')
                    ->size(TextSize::Small)
                ,
                TextEntry::make('deleted_at')->date()
                    ->color('gray')
                    ->size(TextSize::Small)
                    ->visible(fn($state) => $state != null)
                ,
            ])
        ;
    }

    protected static function relatedDataSection(): Section
    {
        return Section::make('Related Data')
            ->icon(Heroicon::Link)
            ->iconColor('primary')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                Callout::make()
                    ->columnSpanFull()
                    ->description('Daftar entitas yang terhubung dengan Bank ini.')
                    ->info()
                    ->color(null)
                ,
            ])
        ;
    }

    public static function _configure(Schema $schema): Schema
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
                                    Fieldset::make('Bank')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('company')
                                                ->formatStateUsing(
                                                    fn($record) => new HtmlString(
                                                        "<strong>{$record->company->alias}</strong></br>{$record->company->name}"
                                                    )
                                                )
                                                ->columnSpanFull()
                                            ,

                                            TextEntry::make('code')
                                                ->badge()
                                                ->color('info')
                                                ->fontFamily(FontFamily::Mono)
                                                ->size(TextSize::Large)
                                            ,
                                            TextEntry::make('name'),
                                            TextEntry::make('description')
                                                ->placeholder('-')
                                                ->columnSpanFull()
                                            ,

                                            TextEntry::make('account_number')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('currency.code')
                                                ->label('Currency')
                                                ->badge()
                                                ->color('info')
                                                ->fontFamily(FontFamily::Mono)
                                                ->size(TextSize::Large)
                                            ,
                                            TextEntry::make('balance')
                                                ->money('IDR', decimalPlaces: 2)
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
                                            TextEntry::make('is_active')
                                                ->label('Status')
                                                ->formatStateUsing(fn($state) => Bank::STATUS_LABELS[$state] ?? '-')
                                                ->badge()
                                                ->color(fn(bool $state) => $state == Bank::STATUS_ACTIVE ? 'success' : 'danger')
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
