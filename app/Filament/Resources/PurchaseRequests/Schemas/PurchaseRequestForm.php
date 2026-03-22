<?php

namespace App\Filament\Resources\PurchaseRequests\Schemas;

use App\Enums\PurchaseRequestStatus;
use App\Models\Item;
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
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Zvizvi\UserFields\Components\UserEntry;

class PurchaseRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
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
                            static::dataSection(), // 1.1

                            static::itemSection(), // 1.2
                        ])
                    ,

                    Grid::make() // right / 2
                        ->columnSpan([
                            'xl' => 1,
                            '2xl' => 1,
                        ])
                        ->schema([
                            static::otherInfoSection(), // 2.1

                            // static::relatedDataSection(), // 2.2
                        ])
                    ,
                ])
            ,
        ]);
    }

    protected static function dataSection(): Section
    {
        return Section::make('Form ' . __('purchase-request.section.main_info.label'))
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
                    // ->contained(false)
                    ->schema([
                        Select::make('warehouse_id')
                            ->label(__('warehouse.model.label'))
                            ->relationship(
                                'warehouse',
                                'name',
                                fn($query) => $query
                                    ->when(
                                        auth()->user()->warehouses()->exists(),
                                        fn($q) => $q->whereIn(
                                            'warehouses.id',
                                            auth()->user()->warehouses->pluck('id')
                                        )
                                    )->orderBy('name')->orderBy('code'),
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($set) {
                                $set('company_id', null);
                                $set('division_id', null);
                                $set('project_id', null);
                                $set('warehouse_address_id', null);
                            })
                            ->required()
                            ->disabledOn('edit')
                            ->dehydrated()
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
                                fn($get, string $operation) =>
                                $operation === 'edit' || blank($get('warehouse_id'))
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

                                    $query->when($companyId, function ($q) use ($companyId) {
                                        $q->whereHas(
                                            'companies',
                                            fn($qq) => $qq->where('companies.id', $companyId)
                                        )->orWhereDoesntHave('companies');
                                    })->orderBy('name')->orderBy('code');
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
                                            $q->where(function ($qq) use ($companyId) {
                                                $qq->whereHas(
                                                    'companies',
                                                    fn($q) => $q->where('companies.id', $companyId)
                                                )->orWhereDoesntHave('companies');
                                            });
                                        })
                                        ->when($warehouseId, function ($q) use ($warehouseId) {
                                            $q->where(function ($qq) use ($warehouseId) {
                                                $qq->whereHas(
                                                    'warehouses',
                                                    fn($q) => $q->where('warehouses.id', $warehouseId)
                                                )->orWhereDoesntHave('warehouses');
                                            });
                                        })
                                        ->where('allow_po', true)
                                        ->orderBy('name')->orderBy('code')
                                    ;
                                }
                            )
                            ->searchable(['name', 'code', 'po_code'])
                            ->getOptionLabelFromRecordUsing(
                                fn($record) =>
                                "{$record->code} / {$record->po_code} | {$record->name}"
                            )
                            // ->preload()
                            ->required()
                            ->disabled(
                                fn($get, string $operation) =>
                                $operation === 'edit' ||
                                blank($get('warehouse_id')) ||
                                blank($get('company_id'))
                            )
                            ->dehydrated()
                        ,
                        Select::make('warehouse_address_id')
                            ->label(__('purchase-request.warehouse_address.label'))
                            ->relationship(
                                'warehouseAddress',
                                'address',
                                fn($query, $get) => $query->where('warehouse_id', $get('warehouse_id'))
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn($record) => "{$record->address} - {$record->city}"
                            )
                            ->searchable()
                            ->preload()
                            ->default(null)
                            ->disabled(fn($get) => blank($get('warehouse_id')))
                            ->live()
                        ,
                    ])
                ,

                Fieldset::make(__('purchase-request.fieldset.info.label'))
                    ->columns(1)
                    // ->contained(false)
                    ->schema([
                        TextInput::make('number')
                            ->label(__('purchase-request.number.label'))
                            ->hint('Auto-generated')
                            ->hintIcon('heroicon-m-information-circle')
                            ->hintIconTooltip('Auto-generated')
                            ->readOnly()
                            ->visibleOn('edit')
                            ->dehydrated(false)
                        ,
                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('purchase-request.description.placeholder'))
                            ->helperText(__('purchase-request.description.helper'))
                            ->live()
                            ->autosize()
                            ->columnSpanFull()
                        ,

                        Select::make('status')
                            ->options(fn($record) => $record->getAvailableStatusOptions())
                            ->native(false)
                            // ->live()
                            ->required()
                            ->disableOptionWhen(function ($value, $record) {
                                if (!$record) {
                                    return false;
                                }

                                return $record && !$record->canChangeStatusTo($value);
                            })
                            ->visibleOn('edit')
                        ,
                    ])
                ,
            ])
            ->columnOrder(1)
        ;
    }

    protected static function itemSection(): Section
    {
        return Section::make('Form ' . __('purchase-request.section.purchase_request_items.label'))
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
                        Select::make('item_id')
                            ->label(
                                __('item.related.code.label') .
                                ' | ' .
                                __('item.related.name.label')
                            )
                            ->relationship('item', 'name')
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->getOptionLabelFromRecordUsing(
                                fn($record) => "{$record->code} | {$record->name}"
                            )
                            ->required()
                            ->searchable(['code', 'name'])
                            ->columnSpan(2)
                        ,
                        TextInput::make('qty')
                            ->placeholder('0')
                            ->suffix(
                                fn($get) => Item::query()
                                    ->whereKey($get('item_id'))
                                    ->value('unit')
                            )
                            ->minValue(0.01)
                            ->required()
                            ->numeric()
                        ,
                        Textarea::make('description')
                            ->label(__('common.description.label'))
                            ->placeholder(__('purchase-request.purchase_request_item.description.placeholder'))
                            ->helperText(__('purchase-request.purchase_request_item.description.helper'))
                            ->autosize()
                            ->columnSpanFull()
                        ,
                    ])
                    ->reorderable()
                    ->orderColumn('sort')
                    ->itemNumbers()
                    ->deleteAction(
                        fn(Action $action) => $action->requiresConfirmation(),
                    )
                    ->minItems(1)
                    ->live()
                ,
            ])
            ->columnOrder(3)
        ;
    }

    protected static function otherInfoSection(): Section
    {
        return Section::make('Form ' . __('purchase-request.section.other_info.label'))
            ->icon(Heroicon::InformationCircle)
            ->iconColor('primary')
            ->description(__('purchase-request.section.other_info.description'))
            ->collapsible()
            ->columnSpanFull()
            ->columns(1)
            ->compact()
            ->schema([
                TextInput::make('memo')
                    ->placeholder(__('purchase-request.memo.placeholder'))
                    ->helperText(__('purchase-request.memo.helper'))
                ,
                TextInput::make('boq')
                    ->label(__('purchase-request.boq.label'))
                    ->placeholder(__('purchase-request.boq.placeholder'))
                    ->helperText(__('purchase-request.boq.helper'))
                ,
                Textarea::make('notes')
                    ->label(__('purchase-request.notes.label'))
                    ->placeholder(__('purchase-request.notes.placeholder'))
                    ->helperText(__('purchase-request.notes.helper'))
                    ->autosize()
                    ->columnSpanFull()
                ,

                Textarea::make('info')
                    ->label(__('purchase-request.info.label'))
                    ->placeholder(__('purchase-request.info.placeholder'))
                    ->helperText(__('purchase-request.info.helper'))
                    ->autosize()
                    ->visible(fn($record, $operation) => $operation === 'edit' && !$record?->hasStatus(PurchaseRequestStatus::DRAFT))
                    ->required(fn($get, $record) => $record?->hasWatchedFieldChanges($get()) === true)
                    ->disabled(fn($get, $record) => $record?->hasWatchedFieldChanges($get()) === false)
                    ->afterStateHydrated(fn($component) => $component->state(null))
                    ->columnSpanFull()
                ,
                TextEntry::make('info')
                    ->label(__('purchase-request.revision_history.label'))
                    ->placeholder('-')
                    ->visible(fn($record, $operation) => $operation === 'edit' && !$record?->hasStatus(PurchaseRequestStatus::DRAFT))
                    ->columnSpanFull()
                    ->formatStateUsing(
                        fn($state) => collect(explode("\n", $state))
                            ->map(fn($line) => "• " . e($line))
                            ->implode('<br>')
                    )
                    ->html()
                    ->color('gray')
                ,

                UserEntry::make('user')
                    ->wrapped()
                    ->visibleOn('edit')
                ,

                TextEntry::make('created_at')->date()
                    ->label(__('common.created_at.label'))
                    ->visibleOn('edit')
                    ->color('gray')
                    ->size(TextSize::Small)
                ,
                TextEntry::make('updated_at')->date()
                    ->label(__('common.updated_at.label'))
                    ->visibleOn('edit')
                    ->color('gray')
                    ->size(TextSize::Small)
                ,
                TextEntry::make('deleted_at')->date()
                    ->label(__('common.deleted_at.label'))
                    ->visibleOn('edit')
                    ->color('gray')
                    ->size(TextSize::Small)
                    ->visible(fn($state) => $state != null)
                ,
            ])
            ->columnOrder(2)
        ;
    }
}
