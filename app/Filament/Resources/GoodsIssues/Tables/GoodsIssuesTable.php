<?php

namespace App\Filament\Resources\GoodsIssues\Tables;

use App\Enums\GoodsIssueType;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Zvizvi\UserFields\Components\UserColumn;

class GoodsIssuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label(__('common.number.label'))
                    ->wrapHeader()
                    ->description(fn($record): ?string => Str::limit($record->description, 32))
                    ->tooltip(fn($record): string => $record->description)
                    ->searchable(['number', 'description'])
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->fontFamily(FontFamily::Mono)
                    ->verticallyAlignStart()
                    ->wrap(false)
                    ->grow(false),
                TextColumn::make('type')
                    ->label(__('goods-issue.type.label'))
                    ->wrapHeader()
                    ->formatStateUsing(fn($state) => '')
                    ->icon(fn($state) => $state?->icon())
                    ->color(fn($state) => $state?->color())
                    ->iconColor(fn($state) => $state?->color())
                    ->tooltip(fn($state) => $state?->label())
                    ->size(TextSize::Large)
                    ->alignCenter()
                    ->verticallyAlignStart()
                    ->wrap(),
                TextColumn::make('status')
                    ->formatStateUsing(fn($state) => '')
                    ->icon(fn($state) => $state?->icon())
                    ->color(fn($state) => $state?->color())
                    ->iconColor(fn($state) => $state?->color())
                    ->tooltip(fn($state) => $state?->label())
                    ->size(TextSize::Large)
                    ->alignCenter()
                    ->verticallyAlignStart()
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label(__('common.created_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap(),
                UserColumn::make('user')
                    ->label(__('common.log_activity.created.label') . ' ' . __('common.log_activity.by'))
                    ->wrapHeader()
                    ->verticallyAlignStart()
                    ->wrap(false)
                    ->wrapped(false)
                    ->grow(false)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('project.name')
                    ->label(__('goods-issue.fieldset.warehouse_project.label'))
                    ->description(
                        fn($record): ?string =>
                        $record->warehouse->name . ' - ' . $record->company->alias . ' - ' . $record->division->name
                    )
                    ->wrapHeader()
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('warehouseAddress.address')
                    ->label(__('goods-issue.warehouse_address.label'))
                    ->wrapHeader()
                    ->limit(20)
                    ->tooltip(fn($state) => $state)
                    ->placeholder('-')
                    ->color('gray')
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('goods_issue_items_count')
                    ->label(__('goods-issue.goods_issue_items.count_label'))
                    ->wrapHeader()
                    ->numeric()
                    ->color('gray')
                    ->alignEnd()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('goods_issue_items_sum_qty')
                    ->label(__('goods-issue.goods_issue_items.sum_qty_label'))
                    ->wrapHeader()
                    ->formatStateUsing(fn($state): string => $state)
                    ->numeric()
                    ->color('gray')
                    ->alignEnd()
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('common.updated_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->color('gray')
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('common.deleted_at.label'))
                    ->wrapHeader()
                    ->date()
                    ->color('gray')
                    ->placeholder('-')
                    ->sortable()
                    ->verticallyAlignStart()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('goods-issue.type.label'))
                    ->options(GoodsIssueType::options())
                    ->native(false)
                    ->multiple()
                    ->preload(),
                SelectFilter::make('warehouse')
                    ->label(__('warehouse.model.plural_label'))
                    ->relationship(
                        'warehouse',
                        'name',
                        fn($query) => $query->when(
                            auth()->user()->warehouses()->exists(),
                            fn($q) => $q->whereIn('warehouses.id', auth()->user()->warehouses->pluck('id')),
                        )->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable(['code', 'name'])
                    ->preload(),
                SelectFilter::make('company')
                    ->label(__('goods-issue.company.label'))
                    ->relationship(
                        'company',
                        'alias',
                        fn($query) => $query->orderBy('alias')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable(['code', 'name', 'alias'])
                    ->preload(),
                SelectFilter::make('division')
                    ->label(__('division.model.plural_label'))
                    ->relationship(
                        'division',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable(['code', 'name'])
                    ->preload(),
                SelectFilter::make('project')
                    ->label(__('project.model.plural_label'))
                    ->relationship(
                        'project',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} / {$record->po_code} | {$record->name}")
                    ->multiple()
                    ->searchable(['name', 'code', 'po_code'])
                    ->preload(),
                TrashedFilter::make()->native(false),
            ])
            ->recordActions([
                ViewAction::make()->hiddenLabel(),
            ], position: RecordActionsPosition::BeforeColumns)
            ->striped()
            ->stackedOnMobile()
            ->contentGrid([])
            ->paginated([5, 10, 25, 50, 100])
            ->paginationMode(PaginationMode::Default)
            ->defaultPaginationPageOption(10);
    }
}
