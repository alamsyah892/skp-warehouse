<?php

namespace App\Filament\Resources\Invoices\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Nomor Invoice')
                    ->searchable()
                    ->sortable()
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold),

                TextColumn::make('invoice_date')
                    ->label('Tanggal Invoice')
                    ->date()
                    ->sortable(),

                TextColumn::make('invoice_due_date')
                    ->label('Jatuh Tempo')
                    ->date()
                    ->sortable(),

                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable(['name', 'code'])
                    ->sortable(),

                TextColumn::make('goodsReceive.number')
                    ->label('Goods Receive')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purchaseOrder.number')
                    ->label('Purchase Order')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('IDR', decimalPlaces: 2)
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->date()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('vendor')
                    ->label('Vendor')
                    ->relationship('vendor', 'name', fn (Builder $query): Builder => $query->orderBy('name')->orderBy('code'))
                    ->searchable(['name', 'code'])
                    ->preload(),

                SelectFilter::make('goodsReceive')
                    ->label('Goods Receive')
                    ->relationship('goodsReceive', 'number', fn (Builder $query): Builder => $query->orderByDesc('id'))
                    ->searchable(['number'])
                    ->preload(),

                Filter::make('invoice_date')
                    ->label('Rentang Tanggal Invoice')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label('Dari'),
                        DatePicker::make('date_until')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'] ?? null,
                                fn (Builder $builder, string $date): Builder => $builder->whereDate('invoice_date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'] ?? null,
                                fn (Builder $builder, string $date): Builder => $builder->whereDate('invoice_date', '<=', $date),
                            );
                    }),

                TrashedFilter::make()->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ]);
    }
}
