<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\PurchaseRequest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseRequests';

    protected static ?string $relatedResource = CompanyResource::class;

    // public function table(Table $table): Table
    // {
    //     return $table
    //         ->headerActions([
    //             CreateAction::make(),
    //         ]);
    // }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->columns([
                TextColumn::make('number')->sortable()->searchable(),
                TextColumn::make('warehouse.name'),
                TextColumn::make('division.name'),
                TextColumn::make('project.name'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => PurchaseRequest::STATUS_LABELS[$state])
                    ->color(fn($state) => PurchaseRequest::STATUS_COLORS[$state]),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
