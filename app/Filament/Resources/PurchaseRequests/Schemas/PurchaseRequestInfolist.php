<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Enums\PurchaseRequestStatus;
use App\Filament\Components\Infolists\ActivityLogTab;
use App\Livewire\PurchaseRequestPurchaseOrdersTable;
use App\Livewire\PurchaseRequestItemsTable;
use App\Models\PurchaseRequestItem;
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
use Illuminate\Contracts\View\View as ViewContract;
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

    protected static function dataSection(): Section|string
    {
        return Section::make(__('purchase-request.section.main_info.label'))
            ->icon(Heroicon::ClipboardDocumentList)
            ->iconColor('primary')
            // ->description(__('purchase-request.section.main_info.description'))
            // ->afterHeader(
            //     EditAction::make()
            //         ->icon(Heroicon::PencilSquare)
            //         ->url(fn($record) => PurchaseRequestResource::getUrl('edit', ['record' => $record])),
            // )
            // ->collapsible()
            ->compact()
            ->footer(fn($record) => self::dataSectionFooter($record))
            ->columns(12)
            ->columnSpanFull()
            ->schema([
                Grid::make()
                    ->columnSpan(7)
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
                    ])
                ,
                Grid::make()
                    ->columnSpan(5)
                    ->schema([
                        TextEntry::make('created_at')
                            ->hiddenLabel()
                            ->date()
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary')
                            ->columnSpanFull()
                        ,

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

                        Grid::make()
                            ->columnSpanFull()
                            ->schema([
                            ])
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
                    ->color($status->color())
                    ->icon($status->icon())
                    ->requiresConfirmation()
                    ->modalHeading(
                        __($status->actionLabel()) .
                        ' ' .
                        __('purchase-request.model.label')
                    )
                    ->modalDescription(
                        __(
                            'purchase-request.status.action.note',
                            ['status' => __($status->label())]
                        )
                    )
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

    protected static function tabSection(): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
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

    protected static function otherInfoSection(): Section
    {
        return Section::make(__('purchase-request.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            // ->description(__('purchase-request.section.other_info.description'))
            ->collapsible()
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                TextEntry::make('notes')
                    ->label(__('purchase-request.notes.label'))
                    ->columnSpanFull()
                    ->placeholder('-')
                    ->color('gray')
                    ->formatStateUsing(fn($state) => nl2br(e($state)))
                    ->html()
                ,
                TextEntry::make('status')
                    ->formatStateUsing(fn($state) => $state?->label())
                    ->icon(fn($state) => $state?->icon())
                    ->badge()
                    ->color(fn($state) => $state?->color())
                ,

                UserEntry::make('user')
                    ->label('Dibuat Oleh')
                    ->wrapped()
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

                TextEntry::make('info')
                    ->label(__('purchase-request.revision_history.label'))
                    ->placeholder('-')
                    ->visible(fn($record) => !$record?->hasStatus(PurchaseRequestStatus::DRAFT))
                    ->columnSpanFull()
                    ->formatStateUsing(
                        fn($state) => collect(explode("\n", $state))
                            ->map(fn($line) => "• " . e($line))
                            ->implode('<br>')
                    )
                    ->html()
                    ->color('gray')
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
            ->columnSpanFull()
            ->columns(2)
            ->compact()
            ->schema([
                RepeatableEntry::make('statusLogs')
                    ->hiddenLabel()
                    ->schema([
                        TextEntry::make('to_status')
                            ->hiddenLabel()
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
                            ->icon(fn($state) => $state?->icon())
                            ->iconColor(fn($state) => $state?->color())
                            ->color('gray')
                        ,
                    ])
                    ->columnSpanFull()
                    ->contained(false)
                ,
            ])
        ;
    }

    public static function purchaseRequestItemSummaryView(PurchaseRequestItem $record): ViewContract
    {
        return view('filament.infolists.purchase-request-item-summary', [
            'itemName' => $record->item?->name,
            'description' => filled($record->description) ? $record->description : null,
        ]);
    }

    protected static function hasRemainingPurchaseRequestItems($record): bool
    {
        return $record->purchaseRequestItems->contains(
            fn($item): bool => $item->getRemainingQty() > 0
        );
    }
}
