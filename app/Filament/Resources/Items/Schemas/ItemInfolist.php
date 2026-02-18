<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\Item;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\EmptyState;
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

class ItemInfolist
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

        return $schema->components([
            Tabs::make()
                ->contained(false)
                ->columnSpanFull()
                // ->vertical()
                ->tabs([
                    Tab::make('Data')
                        ->icon(Heroicon::OutlinedCircleStack)
                        ->schema([
                            Grid::make()
                                ->columns([
                                    'default' => 1,
                                    'lg' => 1,
                                    'xl' => 1,
                                    '2xl' => 4,
                                ])
                                ->schema([
                                    Section::make() // left
                                        // ->contained(false)
                                        ->compact()
                                        ->columnSpan([
                                            '2xl' => 3,
                                        ])
                                        ->schema([
                                            static::dataFieldset(),
                                        ])
                                    ,

                                    Section::make() // right
                                        // ->contained(false)
                                        ->compact()
                                        ->schema([
                                            static::configInfoFieldset(),

                                            // static::relatedDataFieldset(),
                                        ])
                                    ,
                                ])
                            ,
                        ])
                    ,

                    Tab::make('PR item history')
                        ->icon(Heroicon::OutlinedClipboardDocumentList)
                        ->badge(fn($record) => $record->purchase_request_items_count ?: null)
                        ->schema([
                            Grid::make()
                                ->columns([
                                    'default' => 1,
                                    'lg' => 1,
                                    'xl' => 1,
                                    '2xl' => 4,
                                ])
                                ->schema([
                                    Section::make() // left
                                        // ->contained(false)
                                        ->compact()
                                        ->columnSpan([
                                            '2xl' => 3,
                                        ])
                                        ->schema([
                                            RepeatableEntry::make('purchaseRequestItems')
                                                ->columnSpanFull()
                                                ->table([
                                                    TableColumn::make('Purchase request number'),
                                                    TableColumn::make('Warehouse'),
                                                    TableColumn::make('Company'),
                                                    TableColumn::make('Division'),
                                                    TableColumn::make('Project'),
                                                    TableColumn::make('Qty'),
                                                    // TableColumn::make('Deskripsi'),
                                                ])
                                                ->schema([
                                                    TextEntry::make('purchaseRequest.number')
                                                        ->url(
                                                            fn($record) => PurchaseRequestResource::getUrl('view', [
                                                                'record' => $record->purchaseRequest,
                                                            ])
                                                        )
                                                        ->openUrlInNewTab() // optional
                                                        ->color('primary')
                                                        ->icon(Heroicon::ArrowTopRightOnSquare)
                                                        ->iconPosition('after')
                                                        ->wrap(false)
                                                    ,
                                                    TextEntry::make('purchaseRequest.warehouse.name'),
                                                    TextEntry::make('purchaseRequest.company.alias'),
                                                    TextEntry::make('purchaseRequest.division.name'),
                                                    TextEntry::make('purchaseRequest.project.name'),
                                                    TextEntry::make('qty')->numeric()->alignEnd(),
                                                    // TextEntry::make('description'),
                                                ])
                                                ->visible(fn($record) => $record->purchase_request_items_count > 0)
                                            ,
                                            EmptyState::make('No purchase request item yet')
                                                ->description('No purchase request item has been recorded yet.')
                                                ->icon(Heroicon::OutlinedClipboardDocumentList)
                                                ->visible(fn($record) => $record->purchase_request_items_count == 0)
                                                ->contained(false)
                                            ,
                                        ])
                                    ,

                                    Section::make() // right
                                        // ->contained(false)
                                        ->compact()
                                        ->schema([
                                            static::dataFieldset(),

                                            static::configInfoFieldset(),

                                            // static::relatedDataFieldset(),
                                        ])
                                    ,
                                ])
                            ,
                        ])
                    ,

                    ActivityLogTab::make('Logs'),
                ])
            ,
        ]);
    }

    protected static function dataSection(): Section
    {
        return Section::make('Item Information')
            ->icon(Heroicon::Cube)
            ->iconColor('primary')
            ->description('Informasi utama dan identitas dasar item.')
            // ->afterHeader([
            //     Action::make('edit')
            //         ->label('Edit')
            //         ->icon(Heroicon::PencilSquare)
            //         ->url(fn($record) => ItemResource::getUrl('edit', ['record' => $record]))
            //     ,
            // ])
            ->collapsible()
            ->columnSpanFull()
            ->columns(3)
            ->compact()
            ->schema([
                TextEntry::make('name')
                    ->label('Item Name')
                    ->columnSpan(2)
                    ->icon(fn($record) => $record->is_active ? Heroicon::CheckBadge : Heroicon::ExclamationTriangle)
                    ->iconPosition(IconPosition::After)
                    ->iconColor(fn($record) => $record->is_active ? 'success' : 'danger')
                    ->size(TextSize::Large)
                    ->weight(FontWeight::Bold)
                ,

                TextEntry::make('code')
                    ->badge()
                    ->fontFamily(FontFamily::Mono)
                    ->size(TextSize::Large)
                ,

                TextEntry::make('category.parent_full_path')
                    ->label('Category')
                    ->columnSpan(2)
                ,

                TextEntry::make('unit'),

                TextEntry::make('description')
                    ->columnSpan(2)
                    ->placeholder('-')
                ,

                TextEntry::make('type')
                    ->formatStateUsing(fn($state) => Item::TYPE_LABELS[$state] ?? '-')
                    ->badge()
                    ->color(fn($state) => $state == Item::TYPE_STOCKABLE ? 'success' : 'warning')
                ,
            ])
        ;
    }

    protected static function otherInfoSection(): Section
    {
        return Section::make('Other Information')
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->description('Informasi lain terkait item.')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                TextEntry::make('is_active')
                    ->label('Status')
                    ->icon(fn($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle)
                    ->formatStateUsing(fn($state) => Item::STATUS_LABELS[$state])
                    ->badge()
                    ->color(fn($state) => $state == Item::STATUS_ACTIVE ? 'success' : 'danger')
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

    protected static function relatedDataSection(): Fieldset
    {
        return Fieldset::make();
    }

    protected static function tabSection(): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                Tab::make('PR History')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->badge(fn($record) => $record->purchase_request_items_count ?: null)
                    ->schema([
                        TextEntry::make('info')
                            ->hiddenLabel()
                            ->state('Riwayat semua Pengajuan Pembelian yang terkait dengan item ini.')
                            ->color('gray')
                        ,

                        RepeatableEntry::make('purchaseRequestItems')
                            ->columnSpanFull()
                            ->table([
                                TableColumn::make('Purchase request number'),
                                TableColumn::make('Warehouse'),
                                TableColumn::make('Company'),
                                TableColumn::make('Division'),
                                TableColumn::make('Project'),
                                TableColumn::make('Qty'),
                                // TableColumn::make('Deskripsi'),
                            ])
                            ->schema([
                                TextEntry::make('purchaseRequest.number')
                                    ->url(
                                        fn($record) => PurchaseRequestResource::getUrl('view', [
                                            'record' => $record->purchaseRequest,
                                        ])
                                    )
                                    ->openUrlInNewTab() // optional
                                    ->color('primary')
                                    ->icon(Heroicon::ArrowTopRightOnSquare)
                                    ->iconPosition('after')
                                    ->wrap(false)
                                ,
                                TextEntry::make('purchaseRequest.warehouse.name'),
                                TextEntry::make('purchaseRequest.company.alias'),
                                TextEntry::make('purchaseRequest.division.name'),
                                TextEntry::make('purchaseRequest.project.name'),
                                TextEntry::make('qty')->numeric()->alignEnd(),
                                // TextEntry::make('description'),
                            ])
                            ->visible(fn($record) => $record->purchase_request_items_count > 0)
                        ,
                        EmptyState::make('No purchase request item yet')
                            ->description('No purchase request item has been recorded yet.')
                            ->icon(Heroicon::OutlinedClipboardDocumentList)
                            ->visible(fn($record) => $record->purchase_request_items_count == 0)
                            ->contained(false)
                        ,
                    ])
                ,

                ActivityLogTab::make('Logs'),
            ])
        ;
    }
}