<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use App\Models\PurchaseRequest;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PurchaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->description(fn($record): string => $record->description)
                    ->searchable()
                    ->sortable()
                    ->fontFamily(FontFamily::Mono)
                    ->size(TextSize::Large)
                    ->weight(FontWeight::Bold)
                    ->wrap()
                ,
                // TextColumn::make('type')
                //     ->formatStateUsing(fn($state) => PurchaseRequest::TYPE_LABELS[$state])
                //     ->toggleable(isToggledHiddenByDefault: true)
                // ,
                TextColumn::make('warehouse.name')
                    ->wrap()
                ,
                TextColumn::make('company.alias')
                    ->wrap()
                ,
                TextColumn::make('division.name')
                    ->wrap()
                ,
                TextColumn::make('project.name')
                    ->wrap()
                ,
                TextColumn::make('warehouseAddress.address')
                    ->label('Warehouse Address')
                    ->wrapHeader()
                    ->placeholder('-')
                    ->color('gray')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('created_at')
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->wrap()
                ,
                ViewColumn::make('user_profile')
                    ->label('User')
                    ->view('filament.tables.columns.user-profile')
                ,
                TextColumn::make('status')
                    ->formatStateUsing(fn($state) => PurchaseRequest::STATUS_LABELS[$state])
                    ->icon(fn($state) => PurchaseRequest::STATUS_ICONS[$state])
                    ->badge()
                    ->color(fn($state) => PurchaseRequest::STATUS_COLORS[$state])
                    ->grow(false)
                    ->sortable()
                ,

                TextColumn::make('memo')
                    ->searchable()
                    ->placeholder('-')
                    ->color('gray')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('boq')
                    ->label('BOQ')
                    ->searchable()
                    ->placeholder('-')
                    ->color('gray')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,

                TextColumn::make('updated_at')
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->color('gray')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('deleted_at')
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->placeholder('-')
                    ->color('gray')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
            ])
            ->filters([
                SelectFilter::make('warehouse')
                    ->relationship(
                        'warehouse',
                        'name',
                        fn($query) => $query
                            ->when(
                                auth()->user()->warehouses()->exists(),
                                fn($q) => $q->whereIn('warehouses.id', auth()->user()->warehouses->pluck('id'))
                            )
                            ->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                ,

                SelectFilter::make('company')
                    ->relationship(
                        'company',
                        'alias',
                        fn($query) => $query->orderBy('alias')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                ,

                SelectFilter::make('division')
                    ->relationship(
                        'division',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                ,

                SelectFilter::make('project')
                    ->relationship(
                        'project',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                ,

                // SelectFilter::make(name: 'status')
                //     ->options(PurchaseRequest::STATUS_LABELS)
                //     ->native(false)
                // ,

                TrashedFilter::make()->native(false),
            ])
            ->recordActions([
                ViewAction::make()->hiddenLabel(),
            ], position: RecordActionsPosition::BeforeColumns)

            ->striped()
            ->stackedOnMobile()

            ->contentGrid([])
            ->paginated([5, 10, 25, 50, 100])
            ->defaultPaginationPageOption(10)

        ;
    }
}
