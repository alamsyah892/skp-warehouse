<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Models\PurchaseRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
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
                    ->afterStateUpdated(fn($set) => $set('project_id', null))
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
                        fn($query, $get) => $query->where('warehouse_id', $get('warehouse_id'))
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
                    ->live()
                    ->afterStateUpdated(fn($set) => $set('project_id', null))
                    ->required()
                    ->disabledOn('edit')
                    ->dehydrated()
                ,
                Select::make('division_id')
                    ->relationship(
                        'division',
                        'name',
                        // fn($query) => $query->orderBy('name')->orderBy('code'),
                        function ($query, $get) {
                            $companyId = $get('company_id');

                            $query
                                ->when($companyId, function ($q) use ($companyId) {
                                    $q
                                        ->whereHas(
                                            'companies',
                                            fn($qq) =>
                                            $qq->where('companies.id', $companyId)
                                        )
                                        ->orWhereDoesntHave('companies')
                                    ;
                                })

                                ->orderBy('name')
                                ->orderBy('code')
                            ;
                        }
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
                        function ($query, $get) {
                            $warehouseId = $get('warehouse_id');
                            $companyId = $get('company_id');

                            $query
                                ->when($companyId, function ($q) use ($companyId) {
                                    $q
                                        ->whereHas(
                                            'companies',
                                            fn($qq) =>
                                            $qq->where('companies.id', $companyId)
                                        )
                                        ->orWhereDoesntHave('companies')
                                    ;
                                })
                                ->when($warehouseId, function ($q) use ($warehouseId) {
                                    $q->where(function ($qq) use ($warehouseId) {
                                        $qq
                                            ->whereHas(
                                                'warehouses',
                                                fn($w) =>
                                                $w->where('warehouses.id', $warehouseId)
                                            )
                                            ->orWhereDoesntHave('warehouses')
                                        ;
                                    });
                                })

                                ->where('allow_po', true)
                                ->orderBy('name')
                                ->orderBy('code')
                            ;
                        }
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

                TextEntry::make('info')
                    ->label('Revision History')
                    ->placeholder('-')
                    ->visibleOn('edit')
                    ->columnSpanFull()
                    ->formatStateUsing(
                        fn($state) =>
                        collect(explode("\n", $state))
                            ->map(fn($line) => "• " . e($line))
                            ->implode('<br>')
                    )
                    ->html()
                    ->color('gray')
                ,

                Textarea::make('info')
                    ->label('Revision Info')
                    ->placeholder("Ubah quantity Item A / Ubah Item B menjadi Item C")
                    ->visibleOn('edit')
                    ->required()
                    ->afterStateHydrated(fn($component) => $component->state(null))
                    ->columnSpanFull()
                ,

                Select::make('status')
                    ->options(PurchaseRequest::STATUS_LABELS)
                    ->native(false)
                    ->required()
                    ->visibleOn('edit')
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
