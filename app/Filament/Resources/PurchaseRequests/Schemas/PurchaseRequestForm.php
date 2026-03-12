<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Models\PurchaseRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PurchaseRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        // 'lg' => 1,
                        'xl' => 4,
                        '2xl' => 4,
                    ])
                    ->schema([
                        Grid::make() // left / 1
                            ->columnSpan([
                                'xl' => 3,
                                '2xl' => 3,
                            ])
                            ->schema([
                                Section::make('Purchase Request Information')
                                    ->icon(Heroicon::ClipboardDocumentList)
                                    ->iconColor('primary')
                                    ->description('Informasi utama dari Purchase Request.')
                                    ->collapsible()
                                    ->columnSpanFull()
                                    ->columns(2)
                                    ->compact()
                                    ->schema([
                                        Fieldset::make('Warehouse & Project')
                                            ->columns(1)
                                            ->contained(false)
                                            ->schema([
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
                                                    ->afterStateUpdated(fn($set) => $set('company_id', null))
                                                    ->afterStateUpdated(fn($set) => $set('division_id', null))
                                                    ->afterStateUpdated(fn($set) => $set('project_id', null))
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
                                                    ->disabled(
                                                        fn($get) =>
                                                        blank($get('warehouse_id'))
                                                    )
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
                                                    ->disabled(
                                                        fn($get, string $operation) =>
                                                        $operation === 'edit' || blank($get('warehouse_id'))
                                                    )
                                                    ->dehydrated()
                                                ,
                                                Select::make('division_id')
                                                    ->relationship(
                                                        'division',
                                                        'name',
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
                                                    ->disabled(
                                                        fn($get, string $operation) =>
                                                        $operation === 'edit' || blank($get('company_id'))
                                                    )
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
                                                    ->disabled(
                                                        fn($get, string $operation) =>
                                                        $operation === 'edit' || blank($get('warehouse_id')) || blank($get('company_id'))
                                                    )
                                                    ->dehydrated()
                                                ,
                                            ])
                                        ,

                                        Fieldset::make('Request Info')
                                            ->columns(1)
                                            ->contained(false)
                                            ->schema([
                                                TextInput::make('number')
                                                    ->required()
                                                    ->readOnly()
                                                    ->visibleOn('edit')
                                                    ->dehydrated(false)
                                                ,
                                                Textarea::make('description')
                                                    ->columnSpanFull(),

                                                Select::make('status')
                                                    ->options(PurchaseRequest::STATUS_LABELS)
                                                    ->native(false)
                                                    ->required()
                                                    ->visibleOn('edit')
                                                ,
                                            ])
                                        ,
                                    ])
                                    ->columnOrder(1)
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
                                    ->columnOrder(3)
                                ,

                            ])
                        ,

                        Grid::make() // right / 2
                            ->columnSpan([
                                'xl' => 1,
                                '2xl' => 1,
                            ])
                            ->schema([
                                Section::make('Other Information')
                                    ->icon(Heroicon::InformationCircle)
                                    ->iconColor('primary')
                                    ->description('Informasi lain terkait Purchase Request.')
                                    ->collapsible()
                                    ->columnSpanFull()
                                    ->columns(1)
                                    ->compact()
                                    ->schema([
                                        TextInput::make('memo'),
                                        TextInput::make('boq'),
                                        Textarea::make('notes')
                                            ->columnSpanFull(),

                                        Textarea::make('info')
                                            ->label('Revision Info')
                                            ->placeholder("Ubah quantity Item A / Ubah Item B menjadi Item C")
                                            ->visibleOn('edit')
                                            ->required()
                                            ->afterStateHydrated(fn($component) => $component->state(null))
                                            ->columnSpanFull()
                                        ,
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
                                    ])
                                    ->columnOrder(2)
                                ,
                            ])
                        ,
                    ])
                ,
                // Section::make()
                //     ->columns([
                //         'sm' => 3,
                //         'xl' => 6,
                //         '2xl' => 8,
                //     ])
                //     ->schema([


                //     ])
                // ,



            ]);
    }
}
