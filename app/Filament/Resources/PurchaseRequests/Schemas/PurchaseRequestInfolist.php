<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Filament\Components\Infolists\ActivityLogTab;
use App\Filament\Components\Infolists\StatusTimelineSection;
use App\Livewire\PurchaseRequestPurchaseOrdersTable;
use App\Livewire\PurchaseRequestItemsTable;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
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
                    'lg' => 4,
                    // 'xl' => 4,
                    // '2xl' => 4,
                ])
                ->dense()
                ->schema([
                    Grid::make() // left / 1
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                            // 'xl' => 3,
                            // '2xl' => 3,
                        ])
                        ->columns(1)
                        ->schema([
                            static::dataSection(), // 1.1

                            static::tabSection(), // 1.2
                        ])
                    ,

                    Grid::make() // right / 2
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 1,
                            // 'xl' => 1,
                            // '2xl' => 1,
                        ])
                        ->columns(1)
                        ->schema([
                            static::infoSection(), // 2.1

                            StatusTimelineSection::make(), // 2.2
                        ])
                    ,
                ])
            ,
        ]);
    }

    protected static function dataSection(): Section|string
    {
        return Section::make(__('purchase-request.section.main_info.label'))
            ->icon(Heroicon::ClipboardDocumentList)
            ->iconColor('primary')
            ->compact()
            ->footer(fn($record) => self::dataSectionFooter($record))
            ->columns([
                'default' => 1,
                'lg' => 12,
            ])
            ->schema([
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 7,
                    ])
                    ->schema([
                        TextEntry::make('number')
                            ->hiddenLabel()
                            ->icon(Heroicon::Hashtag)
                            ->iconColor('primary')
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->fontFamily(FontFamily::Mono)
                            ->columnSpanFull()
                        ,
                    ])
                ,
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->columns([
                        'default' => 2,
                    ])
                    ->schema([
                        TextEntry::make('status')
                            ->hiddenLabel()
                            ->icon(fn($state) => $state?->icon())
                            ->formatStateUsing(fn($state) => $state?->label())
                            ->size(TextSize::Large)
                            ->color(fn($state) => $state?->color())
                            ->badge()
                        ,
                        TextEntry::make('created_at')
                            ->hiddenLabel()
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary')
                            ->date()
                            ->alignEnd()
                        ,
                    ])
                ,
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 7,
                    ])
                    ->columns([
                        'default' => 2,
                    ])
                    ->schema([
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
                            ->icon(Heroicon::MapPin)
                            ->iconColor('primary')
                            ->formatStateUsing(
                                fn($state, $record) => collect([$state, $record->warehouseAddress?->city])->filter()->join(' - ') ?: '-'
                            )
                            ->html()
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpanFull()
                        ,
                    ])
                ,
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->columns([
                        'default' => 2,
                    ])
                    ->schema([
                        TextEntry::make('description')
                            ->label(__('common.description.label'))
                            ->formatStateUsing(fn($state) => nl2br($state))
                            ->html()
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpanFull()
                        ,
                        TextEntry::make('memo')
                            ->formatStateUsing(fn($state) => nl2br($state))
                            ->html()
                            ->placeholder('-')
                            ->color('gray')
                        ,
                        TextEntry::make('boq')
                            ->label(__('purchase-request.boq.label'))
                            ->formatStateUsing(fn($state) => nl2br($state))
                            ->html()
                            ->placeholder('-')
                            ->color('gray')
                        ,
                    ])
                ,
            ])
        ;
    }

    protected static function dataSectionFooter($record): array
    {
        return collect($record->getNextStatuses())
            ->reject(fn(PurchaseRequestStatus $status): bool => static::shouldHideStatusAction($record, $status))
            ->map(function ($status) use ($record) {
                return Action::make('changeStatus' . $status->value)
                    ->label(__($status->actionLabel()))
                    ->icon($status->icon())
                    ->color($status->color())
                    ->requiresConfirmation()
                    ->modalHeading(__($status->actionLabel()) . ' ' . __('purchase-request.model.label'))
                    ->modalDescription(__('purchase-request.status.action.note', ['status' => __($status->label())]))
                    ->action(function () use ($status, $record) {
                        $record->changeStatus($status);

                        Notification::make()
                            ->success()
                            ->title(__('purchase-request.status.action.changed'))
                            ->send()
                        ;

                        return redirect(request()->header('Referer'));
                    })
                ;
            })
            ->values()
            ->all()
        ; // return array action
    }

    protected static function shouldHideStatusAction($record, PurchaseRequestStatus $status): bool
    {
        if ($status === PurchaseRequestStatus::CANCELED) {
            $hasNotCanceledPO = $record->purchaseOrders()->whereNotIn('status', [
                PurchaseOrderStatus::CANCELED
            ])->exists();

            return $hasNotCanceledPO;
        }

        if ($status === PurchaseRequestStatus::FINISHED) {
            $hasNotFinishedPO = $record->purchaseOrders()->whereNotIn('status', [
                PurchaseOrderStatus::CANCELED,
                PurchaseOrderStatus::FINISHED
            ])->exists();

            $hasRemainingQty = $record->purchaseRequestItems->contains(
                fn($item): bool => $item->getRemainingQty() > 0
            );

            return $hasNotFinishedPO || $hasRemainingQty;
        }

        return false;
    }

    protected static function tabSection(): Tabs|string
    {
        return Tabs::make()
            ->tabs([
                Tab::make(__('purchase-request.section.purchase_request_items.label'))
                    ->icon(Heroicon::Cube)
                    ->badge(fn($record) => $record->purchaseRequestItems()->count() ?: null)
                    ->badgeTooltip(__('purchase-request.purchase_request_items.count_label'))
                    ->schema([
                        // Callout::make()
                        //     ->description(__('purchase-request.section.purchase_request_items.description'))
                        //     ->info()
                        //     ->color(null)
                        // ,

                        Livewire::make(PurchaseRequestItemsTable::class),
                    ])
                ,

                Tab::make(__('purchase-order.model.plural_label'))
                    ->icon(Heroicon::ShoppingCart)
                    ->badge(fn($record) => $record->purchaseOrders()->count() ?: null)
                    ->badgeTooltip(__('purchase-request.purchase_orders.count_label'))
                    ->schema([
                        // Callout::make()
                        //     ->description(__('purchase-request.section.purchase_orders.description'))
                        //     ->info()
                        //     ->color(null)
                        // ,

                        Livewire::make(PurchaseRequestPurchaseOrdersTable::class),
                    ])
                    ->visible(fn($record) => $record->purchaseOrders()->exists())
                ,

                ActivityLogTab::make(__('common.log_activity.label')),
            ])
        ;
    }

    protected static function infoSection(): Section|string
    {
        return Section::make(__('purchase-request.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->schema([
                TextEntry::make('notes')
                    ->label(__('purchase-request.notes.label'))
                    ->formatStateUsing(fn($state) => nl2br($state))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                ,
                UserEntry::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->color('gray')
                ,
                TextEntry::make('updated_at')->date()
                    ->label(__('common.updated_at.label'))
                    ->color('gray')
                ,
                TextEntry::make('deleted_at')->date()
                    ->label(__('common.deleted_at.label'))
                    ->color('gray')
                    ->visible(fn($state) => $state != null)
                ,
                TextEntry::make('info')
                    ->label(__('purchase-order.revision_history.label'))
                    ->formatStateUsing(fn($state) => collect(explode("\n", $state))->map(fn($line) => "• " . $line)->implode('<br>'))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->visible(fn($state) => filled($state))
                ,
            ])
        ;
    }
}
