<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Models\PurchaseRequest;
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
                Select::make('warehouse_id')
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
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn($set) => $set('warehouse_address_id', null))
                    ->required()
                    ->disabledOn('edit')
                    ->dehydrated()
                ,
                Select::make('warehouse_address_id')
                    ->label('Warehouse Address')
                    ->relationship(
                        'warehouseAddress',
                        'address',
                        fn($query, $get) =>
                        $query->where('warehouse_id', $get('warehouse_id'))
                    )
                    ->searchable()
                    ->preload()
                    ->default(null)
                ,
                Select::make('company_id')
                    ->relationship(
                        'company',
                        'alias',
                        fn($query) => $query->orderBy('alias')->orderBy('code'),
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('edit')
                    ->dehydrated()
                ,
                Select::make('division_id')
                    ->relationship(
                        'division',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('edit')
                    ->dehydrated()
                ,
                Select::make('project_id')
                    ->relationship(
                        'project',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code'),
                    )
                    ->searchable(['name', 'code'])
                    ->getOptionLabelFromRecordUsing(
                        fn($record) => "{$record->code} | {$record->name}"
                    )
                    ->preload()
                    ->required()
                    ->disabledOn('edit')
                    ->dehydrated()
                ,
                // Select::make('user_id')
                //     ->relationship('user', 'name')
                //     ->required(),
                // TextInput::make('type')
                //     ->required()
                //     ->numeric()
                //     ->default(1),
                TextInput::make('number')
                    ->required()
                    ->readOnly()
                    ->visibleOn('edit')
                    ->dehydrated(false)
                ,
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('memo'),
                TextInput::make('boq'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Textarea::make('info')
                    ->label('Revision Info')
                    ->columnSpanFull()
                    ->visible(fn(string $operation) => $operation === 'edit')
                    ->required(fn(string $operation) => $operation === 'edit')
                    ->rule(function ($record) {
                        return "different:{$record->info}";
                    })
                ,

                Select::make('status')
                    ->options(PurchaseRequest::STATUS_LABELS)
                    ->native(false)
                    ->required()
                ,

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
                            // ->required()
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
