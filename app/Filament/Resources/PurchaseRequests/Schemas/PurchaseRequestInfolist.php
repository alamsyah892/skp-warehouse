<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Models\PurchaseRequest;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PurchaseRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.alias')
                    ->label('Company'),
                TextEntry::make('warehouse.name')
                    ->label('Warehouse'),
                TextEntry::make('warehouseAddress.address')
                    ->label('Warehouse address')
                    ->placeholder('-'),
                TextEntry::make('division.name')
                    ->label('Division'),
                TextEntry::make('project.name')
                    ->label('Project'),
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('type')
                    ->numeric(),
                TextEntry::make('number'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('memo'),
                TextEntry::make('boq'),
                TextEntry::make('notes')
                    ->columnSpanFull(),
                TextEntry::make('info')
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn(PurchaseRequest $record): bool => $record->trashed()),

                RepeatableEntry::make('purchaseRequestItems')
                    ->columnSpanFull()
                    ->table([
                        TableColumn::make('Item Code'),
                        TableColumn::make('Item Name'),
                        TableColumn::make('Item Unit'),
                        TableColumn::make('Qty'),
                        TableColumn::make('Deskripsi'),
                    ])
                    ->schema([
                        TextEntry::make('item.code'),
                        TextEntry::make('item.name'),
                        TextEntry::make('item.unit'),
                        TextEntry::make('qty')->numeric(),
                        TextEntry::make('description'),
                    ])
                ,
            ]);
    }
}
