<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
// use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequest;
// use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
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
use Zvizvi\UserFields\Components\UserEntry;

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
        return Section::make(__('purchase-request.section.main_info.label'))
            ->icon(Heroicon::ClipboardDocumentList)
            ->iconColor('primary')
            ->description(__('purchase-request.section.main_info.description'))
            // ->afterHeader(
            //     EditAction::make()
            //         ->icon(Heroicon::PencilSquare)
            //         ->url(fn($record) => PurchaseRequestResource::getUrl('edit', ['record' => $record])),
            // )
            ->footer(fn($record) => self::dataSectionFooter($record))
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
                            ->hiddenLabel()
                            ->icon(Heroicon::HomeModern)
                            ->iconColor('primary')
                        ,
                        TextEntry::make('company.alias')
                            ->hiddenLabel()
                            ->icon(Heroicon::BuildingOffice2)
                            ->iconColor('primary')
                        ,
                        TextEntry::make('division.name')
                            ->hiddenLabel()
                            ->icon(Heroicon::Briefcase)
                            ->iconColor('primary')
                        ,
                        TextEntry::make('project.name')
                            ->hiddenLabel()
                            ->icon(Heroicon::Square3Stack3d)
                            ->iconColor('primary')
                        ,

                        TextEntry::make('warehouseAddress.address')
                            ->label(__('purchase-request.warehouse_address.label'))
                            ->columnSpanFull()
                            ->color('gray')
                            ->icon(Heroicon::MapPin)
                            ->iconColor('primary')
                            ->placeholder('-')
                            ->formatStateUsing(
                                fn($state, $record) =>
                                collect([$state, $record->warehouseAddress?->city])
                                    ->filter()
                                    ->join(' - ') ?: '-'
                            )
                        ,

                        TextEntry::make('description')
                            ->label(__('common.description.label'))
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

                        UserEntry::make('user')
                            ->wrapped()
                        ,

                        TextEntry::make('status')
                            ->formatStateUsing(fn($state) => PurchaseRequest::getStatusLabel($state))
                            ->icon(fn($state): mixed => PurchaseRequest::getStatusIcon($state))
                            ->badge()
                            ->color(fn($state) => PurchaseRequest::getStatusColor($state))
                        ,
                    ])
                ,
            ])
        ;
    }

    protected static function dataSectionFooter($record): array
    {
        return collect($record->getNextStatuses())->map(function ($status) use ($record) {
            return Action::make('changeStatus' . $status)
                ->label(__(PurchaseRequest::getStatusActionLabel($status)))
                ->color(PurchaseRequest::getStatusColor($status))
                ->icon(PurchaseRequest::getStatusIcon($status))
                ->requiresConfirmation()
                ->modalHeading(
                    __(PurchaseRequest::getStatusActionLabel($status)) .
                    ' ' .
                    __('purchase-request.model.label')
                )
                ->modalDescription(
                    __(
                        'purchase-request.action.note',
                        ['status' => __(PurchaseRequest::getStatusActionLabel($status))]
                    )
                )
                ->action(function () use ($status, $record) {
                    $record->changeStatus($status);

                    Notification::make()
                        ->success()
                        ->title(__('purchase-request.action.changed'))
                        ->send()
                    ;
                })
            ;
        })
            ->values()
            ->all()
        ; // return array action
    }

    protected static function tabSection(): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                Tab::make(__('purchase-request.section.purchase_request_items.label'))
                    ->icon(Heroicon::OutlinedCube)
                    ->badge(fn($record) => $record->purchaseRequestItems?->count() ?: null)
                    ->badgeTooltip(__('purchase-request.purchase_request_items.count_label'))
                    ->schema([
                        Callout::make()
                            ->description(__('purchase-request.section.purchase_request_items.description'))
                            ->info()
                            ->color(null)
                        ,

                        RepeatableEntry::make('purchaseRequestItems')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('#')->wrapHeader(false),
                                TableColumn::make(__('item.related.code.label')),
                                TableColumn::make(__('item.related.name.label')),
                                TableColumn::make(__('item.related.unit.label'))->wrapHeader(),
                                TableColumn::make('Qty'),
                                TableColumn::make(__('common.description.label')),
                            ])
                            ->schema([
                                TextEntry::make('sort')->label('#')->wrap(false),

                                TextEntry::make('item.code')
                                    ->label(__('item.related.code.label'))
                                    ->fontFamily(FontFamily::Mono)
                                    ->weight(FontWeight::Bold)
                                    ->icon(Heroicon::Hashtag)
                                    ->badge()
                                ,
                                TextEntry::make('item.name')
                                    ->label(__('item.related.name.label'))
                                ,
                                TextEntry::make('item.unit')
                                    ->label(__('item.related.unit.label'))
                                ,
                                TextEntry::make('qty')
                                    ->numeric()
                                    ->alignment(Alignment::End)
                                ,
                                TextEntry::make('description')
                                    ->label(__('common.description.label'))
                                    ->color('gray')
                                    ->placeholder('-')
                                ,
                            ])
                        ,
                    ])
                ,

                ActivityLogTab::make(__('common.log_activity.label')),
            ])
        ;
    }

    protected static function otherInfoSection(): Section
    {
        return Section::make(__('purchase-request.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->description(__('purchase-request.section.other_info.description'))
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                TextEntry::make('memo')
                    ->color('gray')
                    ->placeholder('-')
                ,
                TextEntry::make('boq')
                    ->label(__('purchase-request.boq.label'))
                    ->color('gray')
                    ->placeholder('-')
                ,
                TextEntry::make('notes')
                    ->label(__('purchase-request.notes.label'))
                    ->columnSpanFull()
                    ->placeholder('-')
                    ->color('gray')
                ,
                TextEntry::make('info')
                    ->label(__('purchase-request.info.label'))
                    ->columnSpanFull()
                    ->placeholder('-')
                    ->formatStateUsing(
                        fn($state) =>
                        collect(explode("\n", $state))
                            ->map(fn($line) => "• " . e($line))
                            ->implode('<br>')
                    )
                    ->html()
                    ->color('gray')
                ,

                TextEntry::make('created_at')->date()
                    ->label(__('common.created_at.label'))
                    ->color('gray')
                    ->size(TextSize::Small)
                ,
                TextEntry::make('updated_at')->date()
                    ->label(__('common.updated_at.label'))
                    ->color('gray')
                    ->size(TextSize::Small)
                ,
                TextEntry::make('deleted_at')->date()
                    ->label(__('common.deleted_at.label'))
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
    }
}
