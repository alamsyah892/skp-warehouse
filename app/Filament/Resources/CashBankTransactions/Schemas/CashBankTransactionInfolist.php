<?php

namespace App\Filament\Resources\CashBankTransactions\Schemas;

use App\Enums\TransactionType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class CashBankTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Transaksi Kas/Bank')
                ->icon(Heroicon::Banknotes)
                ->iconColor('primary')
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    'lg' => 2,
                ])
                ->schema([
                    TextEntry::make('number')
                        ->label('Nomor Bukti')
                        ->fontFamily(FontFamily::Mono)
                        ->weight(FontWeight::Bold),

                    TextEntry::make('date')
                        ->label('Tanggal')
                        ->date(),

                    TextEntry::make('type')
                        ->label('Jenis Transaksi')
                        ->badge()
                        ->color(fn (TransactionType $state): string => $state->color())
                        ->formatStateUsing(fn (TransactionType $state): string => $state->label()),

                    TextEntry::make('amount')
                        ->label('Jumlah')
                        ->money('IDR', decimalPlaces: 2),

                    TextEntry::make('company.alias')
                        ->label('Perusahaan'),

                    TextEntry::make('project.name')
                        ->label('Proyek')
                        ->placeholder('-'),

                    TextEntry::make('bank.name')
                        ->label('Bank / Kas'),

                    TextEntry::make('vendor.name')
                        ->label('Vendor')
                        ->placeholder('-'),

                    TextEntry::make('check_number')
                        ->label('Nomor Cek')
                        ->placeholder('-'),

                    TextEntry::make('description')
                        ->label('Keterangan')
                        ->placeholder('-')
                        ->columnSpanFull(),

                    Grid::make()
                        ->columnSpanFull()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('created_at')
                                ->label('Dibuat')
                                ->dateTime()
                                ->color('gray'),
                            TextEntry::make('updated_at')
                                ->label('Diperbarui')
                                ->dateTime()
                                ->color('gray'),
                            TextEntry::make('deleted_at')
                                ->label('Dihapus')
                                ->dateTime()
                                ->color('gray')
                                ->visible(fn ($state): bool => $state !== null),
                        ]),
                ]),
        ]);
    }
}
