<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PurchaseRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->required(),
                Select::make('warehouse_address_id')
                    ->relationship('warehouseAddress', 'address')
                    ->default(null),
                Select::make('division_id')
                    ->relationship('division', 'name')
                    ->required(),
                Select::make('project_id')
                    ->relationship('project', 'name')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('type')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('number')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('memo')
                    ->required(),
                TextInput::make('boq')
                    ->required(),
                Textarea::make('notes')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('info')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->numeric()
                    ->default(1),

                Repeater::make('purchaseRequestItems')
                    // ->hiddenLabel()
                    ->relationship()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Select::make('item')
                            ->relationship('item', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),
                        TextInput::make('qty')
                            ->required()
                            ->numeric()
                        // ->helperText('Example: 123')
                        // ->columnSpan(2)
                        ,
                        Textarea::make('description')
                            ->required()
                            ->columnSpanFull()
                        // ->helperText('Example: Untuk perbaikan')
                        ,
                    ])
                    // ->collapsed()
                    // ->live()
                    ->deleteAction(
                        fn(Action $action) => $action->requiresConfirmation(),
                    )
                    ->addActionLabel('Add new item')
                    ->minItems(1)
                ,
            ]);
    }
}
