<?php

namespace App\Livewire;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PurchaseRequestPurchaseOrdersTable extends TableWidget
{
    public $record;

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query(
                PurchaseOrder::query()
                    ->with([
                        'vendor',
                    ])
                    ->withCount([
                        'purchaseOrderItems',
                    ])
                    ->whereHas(
                        'purchaseRequests',
                        fn ($query) => $query->whereKey($this->record->id)
                    )
            )
            ->columns([
                TextColumn::make('number')
                    ->label(__('purchase-order.number.label'))
                    ->description(fn ($record): string => $record->description)
                    ->searchable()
                    ->sortable()
                    ->fontFamily(FontFamily::Mono)
                    ->size(TextSize::Large)
                    ->weight(FontWeight::Bold)
                    ->wrap(),
                TextColumn::make('vendor.name')
                    ->label(__('vendor.model.label'))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label(__('common.created_at.label'))
                    ->date()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('status')
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->icon(fn ($state) => $state?->icon())
                    ->badge()
                    ->color(fn ($state) => $state?->color())
                    ->sortable(),
                TextColumn::make('purchase_order_items_count')
                    ->label(__('purchase-order.purchase_order_items.count_label'))
                    ->sortable()
                    ->color('gray'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->hiddenLabel()
                    ->url(
                        fn ($record) => PurchaseOrderResource::getUrl('view', [
                            'record' => $record->id,
                        ])
                    ),
            ], position: RecordActionsPosition::BeforeColumns)
            ->defaultSort('id', 'desc')
            ->striped()
            ->stackedOnMobile()
            ->paginated(false);
    }
}
