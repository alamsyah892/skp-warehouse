<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\PurchaseRequest;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class PurchaseRequestInfolist
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
        return Section::make('Purchase Request Information')
            ->icon(Heroicon::ClipboardDocumentList)
            ->iconColor('primary')
            ->description('Informasi utama dari Purchase Request.')
            // ->afterHeader([
            //     Action::make('edit')
            //         ->label('Edit')
            //         ->icon(Heroicon::PencilSquare)
            //         ->url(fn($record) => PurchaseRequestResource::getUrl('edit', ['record' => $record]))
            //     ,
            // ])
            ->collapsible()
            ->columnSpanFull()
            ->columns(3)
            ->compact()
            ->schema([
                Grid::make()
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('number')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->fontFamily(FontFamily::Mono)
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->icon(Heroicon::Hashtag)
                            ->iconColor('primary')
                        ,

                        TextEntry::make('warehouse.name')
                            ->label('Warehouse')
                            ->hiddenLabel()
                            ->icon(Heroicon::HomeModern)
                            ->iconColor('primary')
                        ,
                        TextEntry::make('company.alias')
                            ->label('Company')
                            ->hiddenLabel()
                            ->icon(Heroicon::BuildingOffice2)
                            ->iconColor('primary')
                        ,
                        TextEntry::make('division.name')
                            ->label('Division')
                            ->hiddenLabel()
                            ->icon(Heroicon::Briefcase)
                            ->iconColor('primary')
                        ,
                        TextEntry::make('project.name')
                            ->label('Project')
                            ->hiddenLabel()
                            ->icon(Heroicon::Square3Stack3d)
                            ->iconColor('primary')
                        ,

                        TextEntry::make('warehouseAddress.address')
                            ->label('Warehouse Address')
                            ->columnSpanFull()
                            ->color('gray')
                            ->icon(Heroicon::MapPin)
                            ->iconColor('primary')
                            ->placeholder('-')
                        ,

                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->color('gray')
                            ->placeholder('-')
                        ,
                    ])
                ,
                Grid::make()
                    ->columns(1)
                    ->schema([
                        TextEntry::make('created_at')
                            ->hiddenLabel()
                            ->date()
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary')
                        ,

                        TextEntry::make('user')
                            ->label('Requested By')
                            ->view('filament.tables.columns.user-profile')
                        ,

                        // ImageEntry::make('user.avatar_url')
                        //     ->label('User')
                        //     ->imageSize(40)
                        //     ->circular()
                        //     ->disk('public')
                        //     ->defaultImageUrl(
                        //         function ($record) {
                        //             $name = urlencode($record->user->name);
                        //             return url("https://ui-avatars.com/api/?name={$name}&background=random&color=fff");
                        //         }
                        //     )
                        //     ->extraImgAttributes([
                        //         'alt' => 'Image',
                        //         'loading' => 'lazy',
                        //     ])
                        // ,
                        // TextEntry::make('user.name')
                        //     ->hiddenLabel()
                        // ,

                        TextEntry::make('status')
                            ->formatStateUsing(fn($state) => PurchaseRequest::STATUS_LABELS[$state])
                            ->icon(fn($state): mixed => PurchaseRequest::STATUS_ICONS[$state])
                            ->badge()
                            ->color(fn($state) => PurchaseRequest::STATUS_COLORS[$state])
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
                Tab::make('Purchase Request Items')
                    ->icon(Heroicon::OutlinedCube)
                    ->badge(fn($record) => $record->purchase_request_items_count ?: null)
                    ->schema([
                        Callout::make()
                            ->description('Item yang dipesan untuk Purchase Request ini.')
                            ->info()
                            ->color(null)
                        ,

                        RepeatableEntry::make('purchaseRequestItems')
                            ->label('Purchase Request Items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Item Code'),
                                TableColumn::make('Item Name'),
                                TableColumn::make('Item Unit'),
                                TableColumn::make('Qty'),
                                TableColumn::make('Description'),
                            ])
                            ->schema([
                                TextEntry::make('item.code')
                                    ->fontFamily(FontFamily::Mono)
                                    ->weight(FontWeight::Bold)
                                    ->icon(Heroicon::Hashtag)
                                    ->badge()
                                ,
                                TextEntry::make('item.name')
                                    ->wrap()
                                ,
                                TextEntry::make('item.unit'),
                                TextEntry::make('qty')
                                    ->numeric()
                                    ->alignment(Alignment::Center)
                                ,
                                TextEntry::make('description')
                                    ->wrap()
                                ,
                            ])
                            ->visible(fn($record) => $record->purchase_request_items_count > 0)
                        ,
                        EmptyState::make('No Purchase Request Item yet')
                            ->description('No Purchase Request Item has been recorded yet.')
                            ->icon(Heroicon::OutlinedCube)
                            ->visible(fn($record) => $record->purchase_request_items_count == 0)
                            ->contained(false)
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
            ->description('Informasi lain terkait Purchase Request.')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                // TextEntry::make('type')
                //     ->numeric(),

                TextEntry::make('memo')
                    ->color('gray')
                    ->placeholder('-')
                ,
                TextEntry::make('boq')
                    ->label('BOQ')
                    ->color('gray')
                    ->placeholder('-')
                ,
                TextEntry::make('notes')
                    ->columnSpanFull()
                    ->placeholder('-')
                    ->color('gray')
                ,
                TextEntry::make('info')
                    ->columnSpanFull()
                    ->placeholder('-')
                    ->color('gray')
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

    protected static function relatedDataSection(): Section|string
    {
        return '';
        return Section::make('Related Data')
            ->icon(Heroicon::Link)
            ->iconColor('primary')
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                Callout::make()
                    ->description('Daftar entitas yang terhubung dengan Purchase Request ini.')
                    ->info()
                    ->color(null)
                    ->columnSpanFull()
                ,
            ])
        ;
    }
}
