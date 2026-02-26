<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Filament\Components\Tables\TimestampPanel;
use App\Models\Project;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        TextColumn::make('name')
                            ->searchable()
                            ->sortable()
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->grow(false)
                        ,
                        IconColumn::make('is_active')
                            ->label('Status')
                            ->sortable()
                            ->tooltip(fn($state) => Project::STATUS_LABELS[$state] ?? '-')
                            ->boolean()
                            ->trueIcon(Heroicon::CheckBadge)
                            ->falseIcon(Heroicon::ExclamationTriangle)
                            ->trueColor('success')
                            ->falseColor('warning')
                        ,
                        TextColumn::make('code')
                            ->searchable()
                            ->sortable()
                            ->badge()
                            ->fontFamily(FontFamily::Mono)
                            ->icon(Heroicon::Hashtag)
                            ->iconColor('primary')
                            ->grow(false)
                        ,

                        TextColumn::make('po_code')
                            ->label('Code for PO')
                            ->searchable()
                            ->sortable()
                            ->badge()
                            ->fontFamily(FontFamily::Mono)
                            ->icon(Heroicon::Hashtag)
                            ->iconColor('primary')
                            ->grow(false)
                        ,
                    ]),
                    TextColumn::make('description')
                        ->placeholder('-')
                        ->color('gray')
                    ,
                ]),
                Panel::make([
                    Stack::make([
                        TextColumn::make('allow_po')
                            ->description('Allow PO: ', position: 'above')
                            ->icon(fn($state) => $state ? Heroicon::CheckCircle : Heroicon::XCircle)
                            ->formatStateUsing(fn($state) => $state ? 'Allowed' : 'Blocked')
                            ->badge()
                            ->color(fn(bool $state) => $state ? 'success' : 'danger')
                        ,

                        TimestampPanel::make(),

                        TextColumn::make('companies.alias')
                            ->description("Companies: ", position: 'above')
                            ->badge()
                            ->limitList(3)
                        ,
                        TextColumn::make('warehouses.name')
                            ->description("Warehouses: ", position: 'above')
                            ->badge()
                            ->limitList(3)
                        ,

                        TextColumn::make('purchase_requests_count')
                            ->description("PR count: ", position: 'above')
                            ->sortable()
                        ,
                    ])->space(2),
                ])->collapsible(),
            ])
            ->filters([
                SelectFilter::make('companies')
                    ->relationship(
                        'companies',
                        'alias',
                        fn($query) => $query->orderBy('alias')->orderBy('code')
                    )
                    ->multiple()
                    ->preload()
                ,

                SelectFilter::make('warehouses')
                    ->relationship(
                        'warehouses',
                        'name',
                        fn($query) => $query->orderBy('name')->orderBy('code')
                    )
                    ->multiple()
                    ->preload()
                ,

                SelectFilter::make('allow_po')
                    ->label('Allow PO')
                    ->options([1 => 'Allowed', 0 => 'Blocked',])
                    ->native(false)
                ,

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options(Project::STATUS_LABELS)
                    ->native(false)
                ,

                TrashedFilter::make()->native(false),

                Filter::make('unused_in_pr')
                    ->label('Belum ada di PR')
                    ->indicator('PR count: 0')
                    ->query(
                        fn($query) =>
                        $query->whereDoesntHave('purchaseRequests')
                    )
                ,
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->before(function ($record, DeleteAction $action) {
                    if ($record->purchaseRequests()->exists()) {
                        Notification::make()
                            ->title('Action cannot be continued.')
                            ->body('This Project cannot be deleted because it has Purchase Requests.')
                            ->danger()
                            ->send()
                        ;
                        $action->cancel();
                    }
                }),
                RestoreAction::make(),
            ])
        ;
    }
}