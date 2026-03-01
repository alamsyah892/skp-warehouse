<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use App\Models\PurchaseRequest;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
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
                    ->searchable()
                    ->sortable()
                    ->fontFamily(FontFamily::Mono)
                    ->size(TextSize::Large)
                    ->weight(FontWeight::Bold)
                ,
                // TextColumn::make('type')
                //     ->formatStateUsing(fn($state) => PurchaseRequest::TYPE_LABELS[$state])
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true)
                // ,
                TextColumn::make('warehouse.name'),
                TextColumn::make('company.alias'),
                TextColumn::make('division.name'),
                TextColumn::make('project.name'),
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
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('boq')
                    ->label('BOQ')
                    ->searchable()
                    ->placeholder('-')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('warehouseAddress.address')
                    ->placeholder('-')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true)
                ,

                TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->color('gray')
                ,
                TextColumn::make('updated_at')
                    ->date()
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
                TextColumn::make('deleted_at')
                    ->date()
                    ->sortable()
                    ->placeholder('-')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true)
                ,
            ])
            ->filters([
                SelectFilter::make('warehouse')
                    ->relationship(
                        'warehouse',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code')
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                ,

                SelectFilter::make('company')
                    ->relationship(
                        'company',
                        'alias',
                        fn($query) => $query->orderBy('alias')->orderBy('code')
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                ,

                SelectFilter::make('division')
                    ->relationship(
                        'division',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code')
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                ,

                SelectFilter::make('project')
                    ->relationship(
                        'project',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code')
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                ,

                SelectFilter::make(name: 'status')
                    ->options(PurchaseRequest::STATUS_LABELS)
                    ->native(false)
                ,

                TrashedFilter::make()->native(false),
            ])
            ->recordActions([
                ViewAction::make()->hiddenLabel(),
                // EditAction::make(),
            ])

            ->stackedOnMobile()

            ->contentGrid([])
            ->paginated([5, 10, 25, 50, 100])
            ->defaultPaginationPageOption(10)

        ;
    }
}
