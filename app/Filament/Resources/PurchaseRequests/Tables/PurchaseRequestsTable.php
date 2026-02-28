<?php

namespace App\Filament\Resources\PurchaseRequests\Tables;

use App\Models\PurchaseRequest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
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
                    ->icon(Heroicon::Hashtag)
                    ->iconColor('primary')
                    // ->badge()
                    ->grow(false)
                ,
                TextColumn::make('warehouse.name')
                    ->searchable()
                    ->icon(Heroicon::HomeModern)
                    ->iconColor('primary')
                    // ->badge()
                    ->grow(false)
                ,
                TextColumn::make('company.alias')
                    ->searchable()
                    ->icon(Heroicon::BuildingOffice2)
                    ->iconColor('primary')
                    // ->badge()
                    ->grow(false)
                ,
                // TextColumn::make('warehouseAddress.address')
                //     ->searchable(),
                TextColumn::make('division.name')
                    ->searchable()
                    ->icon(Heroicon::Briefcase)
                    ->iconColor('primary')
                    // ->badge()
                    ->grow(false)
                ,
                TextColumn::make('project.name')
                    ->searchable()
                    ->icon(Heroicon::Square3Stack3d)
                    ->iconColor('primary')
                    // ->badge()
                    ->grow(false)
                ,
                ViewColumn::make('user_profile')
                    ->label('User')
                    ->view('filament.tables.columns.user-profile')
                    // ->extraImgAttributes([
                    //     'alt' => 'Image',
                    //     'loading' => 'lazy',
                    // ])
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                ,
                // TextColumn::make('user.name')
                //     ->searchable()
                // ->icon(Heroicon::User)
                // ->iconColor('primary')
                // ->badge()
                // ->grow(false)
                // ,
                TextColumn::make('type')
                    ->formatStateUsing(fn($state) => PurchaseRequest::TYPE_LABELS[$state])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('memo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('boq')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->formatStateUsing(fn($state) => PurchaseRequest::STATUS_LABELS[$state])
                    ->badge()
                    ->color(fn($state) => PurchaseRequest::STATUS_COLORS[$state])
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->stackedOnMobile()
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])

            ->contentGrid([])
            ->paginated([5, 10, 25, 50, 100])
            ->defaultPaginationPageOption(10)

        ;
    }
}
