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
                                Section::make('Form ' . __('purchase-request.section.main_info.label'))
                                    ->icon(Heroicon::ClipboardDocumentList)
                                    ->iconColor('primary')
                                    ->description(__('purchase-request.section.main_info.description'))
                                    ->collapsible()
                                    ->columnSpanFull()
                                    ->columns(2)
                                    ->compact()
                                    ->schema([
                                        Fieldset::make(__('purchase-request.fieldset.warehouse_project.label'))
                                            ->columns(1)
                                            ->contained(false)
                                            ->schema([
                                                Select::make('warehouse_id')
                                                    ->label(__('warehouse.model.label'))
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
                                                    ->label(__('purchase-request.warehouse_address.label'))
                                                    ->relationship(
                                                        'warehouseAddress',
                                                        'address',
                                                        fn($query, $get) => $query->where('warehouse_id', $get('warehouse_id'))
                                                    )
                                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->address} - {$record->city}")
                                                    ->searchable()
                                                    ->preload()
                                                    ->default(null)
                                                    ->disabled(
                                                        fn($get) =>
                                                        blank($get('warehouse_id'))
                                                    )
                                                ,
                                                Select::make('company_id')
                                                    ->label(__('purchase-request.company.label'))
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
                                                        fn($get, string $operation) => $operation === 'edit' || blank($get('warehouse_id'))
                                                    )
                                                    ->dehydrated()
                                                ,
                                                Select::make('division_id')
                                                    ->label(__('division.model.label'))
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
                                                    ->label(__('project.model.label'))
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
                                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
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

                                        Fieldset::make(__('purchase-request.fieldset.info.label'))
                                            ->columns(1)
                                            ->contained(false)
                                            ->schema([
                                                TextInput::make('number')
                                                    ->label(__('purchase-request.number.label'))
                                                    ->required()
                                                    ->readOnly()
                                                    ->visibleOn('edit')
                                                    ->dehydrated(false)
                                                ,
                                                Textarea::make('description')
                                                    ->label(__('common.description.label'))
                                                    ->columnSpanFull()
                                                ,

                                                Select::make('status')
                                                    ->options(PurchaseRequest::getStatusLabels())
                                                    ->native(false)
                                                    ->required()
                                                    ->visibleOn('edit')
                                                ,
                                            ])
                                        ,
                                    ])
                                    ->columnOrder(1)
                                ,

                                Section::make('Form ' . __('purchase-request.section.purchase_request_items.label'))
                                    ->icon(Heroicon::OutlinedCube)
                                    ->iconColor('primary')
                                    ->description(__('purchase-request.section.purchase_request_items.description'))
                                    ->collapsible()
                                    ->columnSpanFull()
                                    ->columns(2)
                                    ->compact()
                                    ->schema([
                                        Repeater::make('purchaseRequestItems')
                                            ->label(__('purchase-request.purchase_request_items.label'))
                                            ->hiddenLabel()
                                            ->relationship()
                                            ->columnSpanFull()
                                            ->columns(3)
                                            ->schema([
                                                Select::make('item')
                                                    ->label(
                                                        __('item.related.code.label') .
                                                        ' | ' .
                                                        __('item.related.name.label')
                                                    )
                                                    ->relationship('item', 'name')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->columnSpan(2)
                                                ,
                                                TextInput::make('qty')
                                                    ->required()
                                                    ->numeric()
                                                // ->helperText('Example: 123')
                                                // ->columnSpan(2)
                                                ,
                                                Textarea::make('description')
                                                    ->label(__('common.description.label'))
                                                    ->columnSpanFull()
                                                // ->helperText('Example: Untuk perbaikan')
                                                ,
                                            ])
                                            ->collapsible()
                                            // ->live()
                                            ->deleteAction(
                                                fn(Action $action) => $action->requiresConfirmation(),
                                            )
                                            ->minItems(1)
                                        ,
                                    ])
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
                                Section::make('Form ' . __('purchase-request.section.other_info.label'))
                                    ->icon(Heroicon::InformationCircle)
                                    ->iconColor('primary')
                                    ->description(__('purchase-request.section.other_info.description'))
                                    ->collapsible()
                                    ->columnSpanFull()
                                    ->columns(1)
                                    ->compact()
                                    ->schema([
                                        TextInput::make('memo'),
                                        TextInput::make('boq')
                                            ->label(__('purchase-request.boq.label'))
                                        ,
                                        Textarea::make('notes')
                                            ->label(__('purchase-request.notes.label'))
                                            ->columnSpanFull()
                                        ,

                                        Textarea::make('info')
                                            ->label(__('purchase-request.info.label'))
                                            ->placeholder(__('purchase-request.info.placeholder'))
                                            ->visibleOn('edit')
                                            ->required()
                                            ->afterStateHydrated(fn($component) => $component->state(null))
                                            ->columnSpanFull()
                                        ,
                                        TextEntry::make('info')
                                            ->label(__('purchase-request.revision_history.label'))
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
            ])
        ;
    }
}
