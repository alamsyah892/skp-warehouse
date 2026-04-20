<?php

namespace App\Filament\Resources\GoodsReceives\Schemas;

use App\Enums\GoodsReceiveStatus;
use App\Filament\Components\Infolists\ActivityLogTab;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Livewire\GoodsReceiveItemsTable;
use App\Models\GoodsReceive;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Zvizvi\UserFields\Components\UserEntry;

class GoodsReceiveInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    'lg' => 4,
                ])
                ->dense()
                ->schema([
                    Grid::make()
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ])
                        ->columns(1)
                        ->schema([
                            static::dataSection(),
                            static::tabSection(),
                        ]),
                    Grid::make()
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 1,
                        ])
                        ->columns(1)
                        ->schema([
                            static::infoSection(),
                            static::statusTimelineSection(),
                        ]),
                ]),
        ]);
    }

    protected static function dataSection(): Section
    {
        return Section::make(__('goods-receive.section.main_info.label'))
            ->icon(Heroicon::InboxArrowDown)
            ->iconColor('primary')
            ->compact()
            ->footer(fn ($record) => self::dataSectionFooter($record))
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
                    ->columns([
                        'default' => 3,
                        'lg' => 3,
                    ])
                    ->schema([
                        TextEntry::make('number')
                            ->hiddenLabel()
                            ->icon(Heroicon::Hashtag)
                            ->iconColor('primary')
                            ->fontFamily(FontFamily::Mono)
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->columnSpan([
                                'default' => 2,
                                'lg' => 2,
                            ]),
                        TextEntry::make('type')
                            ->hiddenLabel()
                            ->icon(fn ($state) => $state?->icon())
                            ->formatStateUsing(fn ($state) => $state?->label())
                            ->size(TextSize::Large)
                            ->color(fn ($state) => $state?->color())
                            ->badge(),
                    ]),
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->columns([
                        'default' => 3,
                        'lg' => 3,
                    ])
                    ->schema([
                        TextEntry::make('status')
                            ->hiddenLabel()
                            ->icon(fn ($state) => $state?->icon())
                            ->formatStateUsing(fn ($state) => $state?->label())
                            ->size(TextSize::Large)
                            ->color(fn ($state) => $state?->color())
                            ->badge()
                            ->columnSpan([
                                'default' => 2,
                                'lg' => 2,
                            ]),
                        TextEntry::make('created_at')
                            ->hiddenLabel()
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary')
                            ->date(),
                    ]),
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 7,
                    ])
                    ->columns([
                        'default' => 2,
                    ])
                    ->schema([
                        TextEntry::make('purchaseOrder.number')
                            ->label(__('goods-receive.purchase_order.label'))
                            ->placeholder('-')
                            ->fontFamily(FontFamily::Mono)
                            ->weight(FontWeight::Bold)
                            ->icon(Heroicon::ShoppingCart)
                            ->iconColor('primary')
                            ->url(fn (GoodsReceive $record): ?string => $record->purchase_order_id
                                ? PurchaseOrderResource::getUrl('view', ['record' => $record->purchase_order_id])
                                : null)
                            ->openUrlInNewTab()
                            ->columnSpanFull(),
                        TextEntry::make('delivery_order')
                            ->label(__('goods-receive.delivery_order.label'))
                            ->placeholder('-')
                            ->color('gray'),
                        TextEntry::make('description')
                            ->label(__('goods-receive.description.label'))
                            ->placeholder('-')
                            ->color('gray')
                            ->formatStateUsing(fn ($state) => nl2br(e((string) $state)))
                            ->html()
                            ->columnSpanFull(),
                    ]),
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->columns([
                        'default' => 2,
                    ])
                    ->schema([
                        TextEntry::make('warehouse.name')
                            ->label(__('warehouse.model.label'))
                            ->placeholder('-')
                            ->color('gray'),
                        TextEntry::make('company.alias')
                            ->label(__('purchase-request.company.label'))
                            ->placeholder('-')
                            ->color('gray'),
                        TextEntry::make('division.name')
                            ->label(__('division.model.label'))
                            ->placeholder('-')
                            ->color('gray'),
                        TextEntry::make('project.name')
                            ->label(__('project.model.label'))
                            ->placeholder('-')
                            ->color('gray'),
                        TextEntry::make('warehouseAddress.address')
                            ->label(__('purchase-request.warehouse_address.label'))
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function dataSectionFooter(GoodsReceive $record): array
    {
        return collect($record->getNextStatuses())
            ->map(function (GoodsReceiveStatus $status) use ($record): Action {
                return Action::make('changeStatus' . $status->value)
                    ->label(__($status->actionLabel()))
                    ->color($status->color())
                    ->icon($status->icon())
                    ->requiresConfirmation()
                    ->modalHeading(__($status->actionLabel()) . ' ' . __('goods-receive.model.label'))
                    ->modalDescription(__('goods-receive.status.action.note', ['status' => __($status->label())]))
                    ->action(function () use ($status, $record) {
                        $record->changeStatus($status);

                        Notification::make()
                            ->success()
                            ->title(__('goods-receive.status.action.changed'))
                            ->send();

                        return redirect(request()->header('Referer'));
                    });
            })
            ->values()
            ->all();
    }

    protected static function tabSection(): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                Tab::make(__('goods-receive.section.goods_receive_items.label'))
                    ->icon(Heroicon::Cube)
                    ->badge(fn ($record) => $record->goodsReceiveItems?->count() ?: null)
                    ->schema([
                        \Filament\Schemas\Components\Livewire::make(GoodsReceiveItemsTable::class),
                    ]),
                ActivityLogTab::make(__('common.log_activity.label')),
            ]);
    }

    protected static function infoSection(): Section
    {
        return Section::make(__('goods-receive.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->schema([
                TextEntry::make('notes')
                    ->label(__('goods-receive.notes.label'))
                    ->formatStateUsing(fn ($state) => nl2br(e((string) $state)))
                    ->html()
                    ->placeholder('-')
                    ->color('gray'),
                UserEntry::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->color('gray'),
                TextEntry::make('updated_at')
                    ->date()
                    ->label(__('common.updated_at.label'))
                    ->size(TextSize::Small)
                    ->color('gray'),
                TextEntry::make('deleted_at')
                    ->date()
                    ->label(__('common.deleted_at.label'))
                    ->size(TextSize::Small)
                    ->color('gray')
                    ->visible(fn ($state) => $state != null),
                TextEntry::make('info')
                    ->label(__('goods-receive.info.label'))
                    ->formatStateUsing(fn ($state) => collect(explode("\n", (string) $state))->map(fn ($line) => '• ' . e($line))->implode('<br>'))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->visible(fn ($state, $record) => filled($state) && !$record?->hasStatus(GoodsReceiveStatus::RECEIVED)),
            ]);
    }

    protected static function statusTimelineSection(): Section
    {
        return Section::make('Status Timeline')
            ->icon(Heroicon::Clock)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->schema([
                RepeatableEntry::make('statusLogs')
                    ->hiddenLabel()
                    ->schema([
                        TextEntry::make('to_status')
                            ->hiddenLabel()
                            ->icon(fn ($state) => $state?->icon())
                            ->iconColor(fn ($state) => $state?->color())
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
                            ->color('gray'),
                    ])
                    ->contained(false),
            ]);
    }
}

