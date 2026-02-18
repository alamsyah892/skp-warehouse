<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\Project;
use App\Models\PurchaseRequest;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\EmptyState;
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

class ProjectInfolist
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
        return Section::make('Project Information')
            ->icon(Heroicon::Square3Stack3d)
            ->iconColor('primary')
            ->description('Informasi utama dan identitas dasar project.')
            // ->afterHeader([
            //     Action::make('edit')
            //         ->label('Edit')
            //         ->icon(Heroicon::PencilSquare)
            //         ->url(fn($record) => ProjectResource::getUrl('edit', ['record' => $record]))
            //     ,
            // ])
            ->collapsible()
            ->columnSpanFull()
            ->columns(3)
            ->compact()
            ->schema([
                TextEntry::make('name')
                    ->label('Project Name')
                    ->columnSpan(2)
                    ->icon(fn($record) => $record->is_active ? Heroicon::CheckBadge : Heroicon::ExclamationTriangle)
                    ->iconPosition(IconPosition::After)
                    ->iconColor(fn($record) => $record->is_active ? 'success' : 'danger')
                    ->size(TextSize::Large)
                    ->weight(FontWeight::Bold)
                ,

                Grid::make()
                    ->schema([
                        TextEntry::make('code')
                            ->badge()
                            ->fontFamily(FontFamily::Mono)
                            ->size(TextSize::Large)
                        ,

                        TextEntry::make('po_code')
                            ->label('Code for PO')
                            ->badge()
                            ->fontFamily(FontFamily::Mono)
                            ->size(TextSize::Large)
                        ,
                    ])
                ,

                TextEntry::make('description')
                    ->columnSpan(2)
                    ->placeholder('-')
                ,
            ])
        ;
    }

    protected static function otherInfoSection(): Section
    {
        return Section::make('Other Information')
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->description('Informasi lain terkait project.')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                TextEntry::make('allow_po')
                    ->label('Allow PO')
                    ->icon(fn($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle)
                    ->formatStateUsing(fn($state) => $state ? 'Allowed' : 'Blocked')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger')
                    ->columnSpanFull()
                ,

                TextEntry::make('is_active')
                    ->label('Status')
                    ->icon(fn($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle)
                    ->formatStateUsing(fn($state) => Project::STATUS_LABELS[$state])
                    ->badge()
                    ->color(fn($state) => $state == Project::STATUS_ACTIVE ? 'success' : 'danger')
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
            ->description('Daftar entitas yang terhubung dengan project ini.')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                TextEntry::make('companies.alias')
                    ->label(fn($record) => "Companies ({$record->companies_count})")
                    ->badge()
                    ->placeholder('-')
                    ->columnSpanFull()
                ,
                TextEntry::make('warehouses.name')
                    ->label(fn($record) => "Warehouses ({$record->warehouses_count})")
                    ->badge()
                    ->placeholder('-')
                    ->columnSpanFull()
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
                        TextEntry::make('info')
                            ->hiddenLabel()
                            ->state('Riwayat semua Pengajuan Pembelian yang terkait dengan project ini.')
                            ->color('gray')
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
                        EmptyState::make('No purchase request yet')
                            ->description('No purchase request has been recorded yet.')
                            ->icon(Heroicon::OutlinedClipboardDocumentList)
                            ->visible(fn($record) => $record->purchase_requests_count == 0)
                            ->contained(false)
                        ,

                        // RepeatableEntry::make('purchaseRequestItems')
                        //     ->columnSpanFull()
                        //     ->table([
                        //         TableColumn::make('Purchase request number'),
                        //         TableColumn::make('Item code'),
                        //         TableColumn::make('Item name'),
                        //         TableColumn::make('Item unit'),
                        //         TableColumn::make('Qty'),
                        //         // TableColumn::make('Deskripsi'),
                        //     ])
                        //     ->schema([
                        //         TextEntry::make('purchaseRequest.number')
                        //             ->wrap(false)
                        //         ,
                        //         TextEntry::make('item.code')
                        //             ->badge()
                        //             ->color('info')
                        //             ->fontFamily(FontFamily::Mono)
                        //             ->size(TextSize::Large)
                        //         ,
                        //         TextEntry::make('item.name'),
                        //         TextEntry::make('item.unit'),
                        //         TextEntry::make('qty')->numeric()->alignEnd(),
                        //         // TextEntry::make('description'),
                        //     ])
                        //     ->visible(fn ($record) => $record->purchase_request_items_count > 0)
                        // ,
                        // EmptyState::make('No purchase request item yet')
                        //     ->description('No purchase request item has been recorded yet.')
                        //     ->icon(Heroicon::OutlinedClipboardDocumentList)
                        //     ->visible(fn($record) => $record->purchase_request_items_count == 0)
                        //     ->contained(false)
                        // ,

                    ])
                ,

                ActivityLogTab::make('Logs'),
            ])
        ;
    }
}
