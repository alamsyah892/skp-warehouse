<?php

namespace App\Filament\Resources\GoodsIssues\Schemas;

use App\Enums\GoodsIssueStatus;
use App\Filament\Components\Infolists\ActivityLogTab;
use App\Filament\Components\Infolists\StatusTimelineSection;
use App\Livewire\GoodsIssueItemsTable;
use App\Models\GoodsIssue;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
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

class GoodsIssueInfolist
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
                            StatusTimelineSection::make(),
                        ]),
                ]),
        ]);
    }

    protected static function dataSection(): Section
    {
        return Section::make(__('goods-issue.section.main_info.label'))
            ->icon(Heroicon::RectangleStack)
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
                            ->icon(fn($state) => $state?->icon())
                            ->formatStateUsing(fn($state) => $state?->label())
                            ->size(TextSize::Large)
                            ->color(fn($state) => $state?->color())
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
                            ->icon(fn($state) => $state?->icon())
                            ->formatStateUsing(fn($state) => $state?->label())
                            ->size(TextSize::Large)
                            ->color(fn($state) => $state?->color())
                            ->badge()
                            ->columnSpan([
                                'default' => 2,
                                'lg' => 2,
                            ]),
                        TextEntry::make('created_at')
                            ->hiddenLabel()
                            ->icon(Heroicon::CalendarDays)
                            ->iconColor('primary')
                            ->date()
                            ->alignEnd(),
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
                        TextEntry::make('warehouse.name')
                            ->hiddenLabel()
                            ->icon(Heroicon::HomeModern)
                            ->iconColor('primary'),
                        TextEntry::make('company.alias')
                            ->hiddenLabel()
                            ->icon(Heroicon::BuildingOffice2)
                            ->iconColor('primary'),
                        TextEntry::make('division.name')
                            ->hiddenLabel()
                            ->icon(Heroicon::Briefcase)
                            ->iconColor('primary'),
                        TextEntry::make('project.name')
                            ->hiddenLabel()
                            ->icon(Heroicon::Square3Stack3d)
                            ->iconColor('primary'),
                        TextEntry::make('warehouseAddress.address')
                            ->label(__('goods-issue.warehouse_address.label'))
                            ->icon(Heroicon::MapPin)
                            ->iconColor('primary')
                            ->formatStateUsing(
                                fn($state, $record) => collect([$state, $record->warehouseAddress?->city])->filter()->join(' - ') ?: '-'
                            )
                            ->html()
                            ->placeholder('-')
                            ->color('gray')
                            ->columnSpanFull(),
                    ]),
                Grid::make()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 5,
                    ])
                    ->columns([
                        'default' => 1,
                    ])
                    ->schema([
                        TextEntry::make('description')
                            ->label(__('common.description.label'))
                            ->columnSpanFull()
                            ->color('gray')
                            ->placeholder('-')
                            ->formatStateUsing(fn($state) => nl2br(e($state)))
                            ->html(),
                    ]),
            ]);
    }

    protected static function dataSectionFooter(GoodsIssue $record): array
    {
        return collect($record->getNextStatuses())
            ->map(function (GoodsIssueStatus $status) use ($record): Action {
                return Action::make('changeStatus' . $status->value)
                    ->label(__($status->actionLabel()))
                    ->color($status->color())
                    ->icon($status->icon())
                    ->requiresConfirmation()
                    ->modalHeading(__($status->actionLabel()) . ' ' . __('goods-issue.model.label'))
                    ->modalDescription(__('goods-issue.status.action.note', ['status' => __($status->label())]))
                    ->action(function () use ($status, $record) {
                        $record->changeStatus($status);

                        Notification::make()
                            ->success()
                            ->title(__('goods-issue.status.action.changed'))
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
                Tab::make(__('goods-issue.section.goods_issue_items.label'))
                    ->icon(Heroicon::Cube)
                    ->badge(fn($record) => $record->goodsIssueItems?->count() ?: null)
                    ->schema([
                        Livewire::make(GoodsIssueItemsTable::class),
                    ]),
                ActivityLogTab::make(__('common.log_activity.label')),
            ]);
    }

    protected static function infoSection(): Section
    {
        return Section::make(__('goods-issue.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->schema([
                TextEntry::make('notes')
                    ->label(__('goods-issue.notes.label'))
                    ->formatStateUsing(fn($state) => nl2br(e($state)))
                    ->html()
                    ->placeholder('-')
                    ->color('gray'),
                UserEntry::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->color('gray'),
                TextEntry::make('updated_at')->date()
                    ->label(__('common.updated_at.label'))
                    ->size(TextSize::Small)
                    ->color('gray'),
                TextEntry::make('deleted_at')->date()
                    ->label(__('common.deleted_at.label'))
                    ->size(TextSize::Small)
                    ->color('gray')
                    ->visible(fn($state) => $state != null),
                TextEntry::make('info')
                    ->label(__('goods-issue.revision_history.label'))
                    ->formatStateUsing(fn($state) => collect(explode("\n", $state))->map(fn($line) => '&#8226; ' . e($line))->implode('<br>'))
                    ->html()
                    ->placeholder('-')
                    ->color('gray')
                    ->visible(fn($state) => filled($state)),
            ]);
    }
}
