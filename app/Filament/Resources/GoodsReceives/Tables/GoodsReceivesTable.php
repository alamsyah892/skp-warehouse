<?php

namespace App\Filament\Resources\GoodsReceives\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GoodsReceivesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label(__('goods-receive.number.label'))
                    ->searchable()
                    ->sortable()
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->wrap(),
                TextColumn::make('purchaseOrder.number')
                    ->label(__('goods-receive.purchase_order.label'))
                    ->searchable()
                    ->sortable()
                    ->fontFamily(FontFamily::Mono)
                    ->color('gray')
                    ->wrap(),
                TextColumn::make('type')
                    ->label(__('goods-receive.type.label'))
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->icon(fn ($state) => $state?->icon())
                    ->badge()
                    ->color(fn ($state) => $state?->color())
                    ->sortable(),
                TextColumn::make('status')
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->icon(fn ($state) => $state?->icon())
                    ->badge()
                    ->color(fn ($state) => $state?->color())
                    ->sortable(),
                TextColumn::make('goods_receive_items_count')
                    ->label(__('goods-receive.goods_receive_items.label'))
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('common.created_at.label'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
