<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Enums\PurchaseRequestStatus;
use App\Filament\Components\Infolists\ActivityLogTab;
use App\Livewire\PurchaseRequestPurchaseOrdersTable;
use App\Livewire\PurchaseRequestItemsTable;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
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

                            static::relatedDataSection(), // 2.2
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
                            ->fontFamily(FontFamily::Mono)
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
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
                            ->badge()
                            ->color(fn($state) => $state?->color())
                        ,
                        TextEntry::make('created_at')
                            ->hiddenLabel()
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary')
                            ->date()
                        ,
                    ])
                ,
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 7,
                    ])
                    ->columns([
                        'default' => 2
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
                                fn($state, $record) =>
                                // $state . "</br>" . $record->warehouseAddress?->city
                                collect([$state, $record->warehouseAddress?->city])
                                    ->filter()
                                    ->join(' - ') ?: '-'
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
                            ->columnSpanFull()
                            ->color('gray')
                            ->placeholder('-')
                            ->formatStateUsing(fn($state) => nl2br(e($state)))
                            ->html()
                        ,

                        TextEntry::make('memo')
                            ->color('gray')
                            ->placeholder('-')
                            ->formatStateUsing(fn($state) => nl2br(e($state)))
                            ->html()
                        ,
                        TextEntry::make('boq')
                            ->label(__('purchase-request.boq.label'))
                            ->color('gray')
                            ->placeholder('-')
                            ->formatStateUsing(fn($state) => nl2br(e($state)))
                            ->html()
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

    public static function shouldHideStatusAction($record, PurchaseRequestStatus $status): bool
    {
        if ($status === PurchaseRequestStatus::ORDERED) {
            return true;
        }

        if ($status === PurchaseRequestStatus::FINISHED) {
            return static::hasRemainingPurchaseRequestItems($record);
        }

        if ($status !== PurchaseRequestStatus::CANCELED) {
            return false;
        }

        return $record->purchaseRequestItems->contains(
            fn($item): bool => $item->getOrderedQty() > 0
        );
    }

    protected static function hasRemainingPurchaseRequestItems($record): bool
    {
        return $record->purchaseRequestItems->contains(
            fn($item): bool => $item->getRemainingQty() > 0
        );
    }

    protected static function tabSection(): Tabs|string
    {
        return Tabs::make()
            ->tabs([
                Tab::make(__('purchase-request.section.purchase_request_items.label'))
                    ->icon(Heroicon::Cube)
                    ->badge(fn($record) => $record->purchaseRequestItems?->count() ?: null)
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
                    ->schema([
                        Livewire::make(PurchaseRequestPurchaseOrdersTable::class),
                    ])
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
            ->columns([
                'lg' => 2
            ])
            ->schema([
                TextEntry::make('notes')
                    ->label(__('purchase-request.notes.label'))
                    ->formatStateUsing(fn($state) => nl2br(e($state)))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->columnSpanFull()
                ,

                UserEntry::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->columnSpanFull()
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

                TextEntry::make('info')
                    ->label(__('purchase-request.revision_history.label'))
                    ->formatStateUsing(
                        fn($state) => collect(explode("\n", $state))
                            ->map(fn($line) => "• " . e($line))
                            ->implode('<br>')
                    )
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->visible(fn($record) => !$record?->hasStatus(PurchaseRequestStatus::DRAFT))
                    ->columnSpanFull()
                ,
            ])
        ;
    }

    protected static function relatedDataSection(): Section|string
    {
        return Section::make('Status Timeline')
            ->icon(Heroicon::Clock)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->columnSpanFull()
            ->schema([
                RepeatableEntry::make('statusLogs')
                    ->hiddenLabel()
                    ->schema([
                        TextEntry::make('to_status')
                            ->hiddenLabel()
                            ->icon(fn($state) => $state?->icon())
                            ->iconColor(fn($state) => $state?->color())
                            ->formatStateUsing(function ($state, $record) {
                                $status = $state?->label();
                                $user = $record->user?->name ?? 'System';
                                $date = $record->created_at->format('M d, Y');
                                $note = $record->note ? '<br>Note: ' . $record->note : '';

                                return __('common.log_format_with_date', [
                                    'date' => $date,
                                    'status' => $status,
                                    'user' => $user,
                                ]) . $note;
                            })
                            ->html()
                            ->color('gray')
                        ,
                    ])
                    ->contained(false)
                ,
            ])
        ;
    }
}
