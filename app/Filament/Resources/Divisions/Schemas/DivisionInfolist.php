<?php

namespace App\Filament\Resources\Divisions\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\Division;
use App\Models\PurchaseRequest;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
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

class DivisionInfolist
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
        return Section::make('Division Information')
            ->icon(Heroicon::Briefcase)
            ->iconColor('primary')
            ->description('Informasi utama dan identitas dasar Divisi.')
            // ->afterHeader([
            //     Action::make('edit')
            //         ->label('Edit')
            //         ->icon(Heroicon::PencilSquare)
            //         ->url(fn($record) => WarehouseResource::getUrl('edit', ['record' => $record]))
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
                        TextEntry::make('description')
                            ->columnSpanFull()
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
                Tab::make('PR History')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->badge(fn($record) => $record->purchase_requests_count ?: null)
                    ->schema([
                        Callout::make()
                            ->description('Riwayat semua Pengajuan Pembelian yang terkait dengan Divisi ini.')
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
            ->description('Informasi lain terkait Divisi.')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                TextEntry::make('is_active')
                    ->label('Status')
                    ->icon(fn($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle)
                    ->formatStateUsing(fn($state) => Division::STATUS_LABELS[$state])
                    ->badge()
                    ->color(fn($state) => $state == Division::STATUS_ACTIVE ? 'success' : 'warning')
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
                    ->description('Daftar entitas yang terhubung dengan Divisi ini.')
                    ->info()
                    ->color(null)
                ,

                TextEntry::make('companies.alias')
                    ->label(fn($record) => "Companies ({$record->companies_count})")
                    ->badge()
                    ->placeholder('-')
                    ->columnSpanFull()
                ,
            ])
        ;
    }
}
