<?php

namespace App\Livewire;

use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\PurchaseRequestItem;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class ItemPurchaseRequestItemsTable extends TableWidget
{
    public $record;

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->query(
                PurchaseRequestItem::query()
                    ->where('item_id', $this->record->id)
                    ->forUserWarehouses(Auth::user())
            )
            ->columns([
                TextColumn::make('purchaseRequest.number')
                    ->label('Purchase Request Number')
                    ->description(fn($record): string => $record->purchaseRequest->description)
                    ->searchable()
                    ->sortable()
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->wrap()
                ,
                TextColumn::make('purchaseRequest.warehouse.name')
                    ->wrap()
                ,
                TextColumn::make('purchaseRequest.company.alias')
                    ->wrap()
                ,
                TextColumn::make('purchaseRequest.division.name')
                    ->wrap()
                ,
                TextColumn::make('purchaseRequest.project.name')
                    ->wrap()
                ,

                TextColumn::make('purchaseRequest.created_at')
                    ->label('PR Created')
                    ->wrapHeader()
                    ->date()
                    ->sortable()
                    ->wrap()
                //     ->toggleable(isToggledHiddenByDefault: true)
                ,
                // TextColumn::make('purchaseRequest.updated_at')
                //     ->label('PR Updated')
                //     ->wrapHeader()
                //     ->date()
                //     ->sortable()
                //     ->color('gray')
                //     ->wrap()
                //     ->toggleable(isToggledHiddenByDefault: true)
                // ,
                // TextColumn::make('purchaseRequest.deleted_at')
                //     ->label('PR Deleted')
                //     ->wrapHeader()
                //     ->date()
                //     ->sortable()
                //     ->placeholder('-')
                //     ->color('gray')
                //     ->wrap()
                //     ->toggleable(isToggledHiddenByDefault: true)
                // ,

                TextColumn::make('qty')->numeric()->alignEnd()
                    ->sortable(),
                TextColumn::make('description')
                    ->wrapHeader()
                    ->placeholder('-')
                    ->color('gray')
                    ->wrap()
                // ->toggleable(isToggledHiddenByDefault: true)
                ,

            ])
            ->filters([
                // TrashedFilter::make()->native(false),
            ])
            ->recordActions([
                ViewAction::make()->hiddenLabel()
                    ->url(
                        fn($record) => PurchaseRequestResource::getUrl('view', [
                            'record' => $record->purchase_request_id,
                        ])
                    )
                ,
            ], position: RecordActionsPosition::BeforeColumns)

            ->striped()
            ->stackedOnMobile()

            ->contentGrid([])
            ->paginated([5, 10, 25, 50, 100])
            ->paginationMode(PaginationMode::Default)
            ->defaultPaginationPageOption(10)
        ;
    }
}
