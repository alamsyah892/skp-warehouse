<?php

namespace App\Filament\Resources\CashBankTransactions\Tables;

use App\Enums\TransactionType;
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

class CashBankTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold),

                TextColumn::make('voucher_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),

                TextColumn::make('company.alias')
                    ->label('Perusahaan')
                    ->searchable(['alias', 'name'])
                    ->sortable(),

                TextColumn::make('project.name')
                    ->label('Proyek')
                    ->placeholder('-')
                    ->searchable(['name', 'code'])
                    ->sortable(),

                TextColumn::make('bank.name')
                    ->label('Bank / Kas')
                    ->searchable(['name', 'code'])
                    ->sortable(),

                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->placeholder('-')
                    ->searchable(['name', 'code'])
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Jenis Transaksi')
                    ->badge()
                    ->color(fn(TransactionType $state): string => $state->color())
                    ->formatStateUsing(fn(TransactionType $state): string => $state->label())
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR', decimalPlaces: 2)
                    ->alignEnd()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('company')
                    ->label('Perusahaan')
                    ->relationship('company', 'alias', fn($query) => $query->orderBy('alias')->orderBy('code'))
                    ->searchable(['alias', 'code', 'name'])
                    ->preload(),

                SelectFilter::make('bank')
                    ->label('Bank / Kas')
                    ->relationship('bank', 'name', fn($query) => $query->orderBy('name')->orderBy('code'))
                    ->searchable(['name', 'code'])
                    ->preload(),

                SelectFilter::make('type')
                    ->label('Jenis Transaksi')
                    ->options(TransactionType::options())
                    ->native(false),

                Filter::make('date')
                    ->label('Rentang Tanggal')
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
                                fn(Builder $q, string $date): Builder => $q->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'] ?? null,
                                fn(Builder $q, string $date): Builder => $q->whereDate('date', '<=', $date),
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
