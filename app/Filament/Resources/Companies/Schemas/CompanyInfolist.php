<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\Company;
use App\Models\PurchaseRequest;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
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

class CompanyInfolist
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

                            static::relatedDataSection(), // 2.2
                        ])
                    ,
                ])
            ,
        ]);
    }

    protected static function dataSection(): Section
    {
        return Section::make('Company Information')
            ->icon(Heroicon::BuildingOffice2)
            ->iconColor('primary')
            ->description('Informasi utama dan identitas dasar Perusahaan.')
            // ->afterHeader([
            //     Action::make('edit')
            //         ->label('Edit')
            //         ->icon(Heroicon::PencilSquare)
            //         ->url(fn($record) => CompanyResource::getUrl('edit', ['record' => $record]))
            //     ,
            // ])
            ->collapsible()
            ->columnSpanFull()
            ->columns(3)
            ->compact()
            ->schema([
                TextEntry::make('alias')
                    ->label('Business Name / Alias')
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
                        TextEntry::make('name')
                            ->label('Company Name')
                        ,
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('-')
                        ,

                        TextEntry::make('address')
                            ->columnSpanFull()
                        ,

                        TextEntry::make('city')
                        ,
                        TextEntry::make('post_code')
                            ->placeholder('-')
                        ,

                        TextEntry::make('contact_person')
                            ->placeholder('-')
                        ,
                        TextEntry::make('contact_person_position')
                            ->placeholder('-')
                        ,

                        TextEntry::make('phone')
                            ->placeholder('-')
                        ,
                        TextEntry::make('fax')
                            ->placeholder('-')
                        ,
                    ])
                ,
                Grid::make()
                    ->columns(1)
                    ->schema([
                        TextEntry::make('email')
                            ->label('Email address')
                            ->placeholder('-')
                        ,
                        TextEntry::make('website')
                            ->placeholder('-')
                        ,
                        TextEntry::make('tax_number')
                            ->placeholder('-')
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
                Tab::make('Banks')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->badge(fn($record) => $record->banks_count ?: null)
                    ->schema([
                        RepeatableEntry::make('banks')
                            ->table([
                                TableColumn::make('Code'),
                                TableColumn::make('Name'),
                                TableColumn::make('Account Number'),
                            ])
                            ->schema([
                                TextEntry::make('code')
                                    ->fontFamily(FontFamily::Mono)
                                ,
                                TextEntry::make('name'),
                                TextEntry::make('account_number')
                                    ->label('Account Number')
                                    ->placeholder('-')
                                ,
                            ])
                        ,
                    ])
                ,
                Tab::make('PR History')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->badge(fn($record) => $record->purchase_requests_count ?: null)
                    ->schema([
                        Callout::make()
                            ->description('Riwayat semua Pengajuan Pembelian yang terkait dengan Perusahaan ini.')
                            ->info()
                            ->color(null)
                        ,

                        RepeatableEntry::make('purchaseRequests')
                            ->columnSpanFull()
                            ->table([
                                TableColumn::make('Number'),
                                TableColumn::make('Warehouse')
                                // ->hiddenHeaderLabel(fn($record) => $record->companies_count == 1)
                                ,
                                TableColumn::make('Company')
                                // ->hiddenHeaderLabel(fn($record) => $record->companies_count == 1)
                                ,
                                TableColumn::make('Division'),
                                // TableColumn::make('Deskripsi'),
                                TableColumn::make('Status'),
                            ])
                            ->schema([
                                TextEntry::make('number')
                                    ->url(
                                        fn($record) => PurchaseRequestResource::getUrl('view', [
                                            'record' => $record->id,
                                        ])
                                    )
                                    ->openUrlInNewTab() // optional
                                    ->color('primary')
                                    ->icon(Heroicon::ArrowTopRightOnSquare)
                                    ->iconPosition(IconPosition::After)
                                    ->wrap(false)
                                ,
                                TextEntry::make('warehouse.name'),
                                TextEntry::make('company.alias'),
                                TextEntry::make('division.name'),
                                // TextEntry::make('description'),
                                TextEntry::make('status')
                                    ->formatStateUsing(fn($state) => PurchaseRequest::STATUS_LABELS[$state])
                                    ->badge()
                                    ->color(fn($state) => PurchaseRequest::STATUS_COLORS[$state])
                                ,
                            ])
                            ->visible(fn($record) => $record->purchase_requests_count > 0)
                        ,
                    ])
                ,

                ActivityLogTab::make('Activity Logs'),
            ])
        ;
    }

    protected static function otherInfoSection(): Section
    {
        return Section::make('Other Information')
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->description('Informasi lain terkait Perusahaan.')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                TextEntry::make('is_active')
                    ->label('Status')
                    ->icon(fn($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle)
                    ->formatStateUsing(fn($state) => Company::STATUS_LABELS[$state])
                    ->badge()
                    ->color(fn($state) => $state == Company::STATUS_ACTIVE ? 'success' : 'warning')
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
                    ->description('Daftar entitas yang terhubung dengan Perusahaan ini.')
                    ->info()
                    ->color(null)
                ,

                TextEntry::make('warehouses.name')
                    ->label(fn($record) => "Warehouses ({$record->warehouses_count})")
                    ->badge()
                    ->placeholder('-')
                    ->columnSpanFull()
                ,
                TextEntry::make('divisions.name')
                    ->label(fn($record) => "Divisions ({$record->divisions_count})")
                    ->badge()
                    ->placeholder('-')
                    ->columnSpanFull()
                ,
                TextEntry::make('projects.name')
                    ->label(fn($record) => "Projects ({$record->projects_count})")
                    ->badge()
                    ->placeholder('-')
                    ->columnSpanFull()
                ,
            ])
        ;
    }
}
