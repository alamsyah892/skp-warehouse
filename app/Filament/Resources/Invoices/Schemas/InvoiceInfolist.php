<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\PurchaseOrderTaxType;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Invoice')
                ->icon(Heroicon::DocumentText)
                ->iconColor('primary')
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    'lg' => 2,
                ])
                ->schema([
                    TextEntry::make('number')
                        ->label('Nomor Invoice')
                        ->fontFamily(FontFamily::Mono)
                        ->weight(FontWeight::Bold),

                    TextEntry::make('total_amount')
                        ->label('Total Amount')
                        ->money('IDR', decimalPlaces: 2),

                    TextEntry::make('invoice_date')
                        ->label('Tanggal Invoice')
                        ->date(),

                    TextEntry::make('invoice_due_date')
                        ->label('Tanggal Jatuh Tempo')
                        ->date(),

                    TextEntry::make('vendor.name')
                        ->label('Vendor'),

                    TextEntry::make('goodsReceive.number')
                        ->label('Goods Receive')
                        ->fontFamily(FontFamily::Mono),

                    TextEntry::make('purchaseOrder.number')
                        ->label('Purchase Order')
                        ->fontFamily(FontFamily::Mono),

                    TextEntry::make('tax_type')
                        ->label('Tipe Pajak')
                        ->formatStateUsing(fn (PurchaseOrderTaxType $state): string => $state->label()),

                    TextEntry::make('tax_percentage')
                        ->label('Persentase Pajak')
                        ->suffix('%'),

                    TextEntry::make('discount')
                        ->label('Diskon')
                        ->money('IDR', decimalPlaces: 2),

                    TextEntry::make('rounding')
                        ->label('Pembulatan')
                        ->money('IDR', decimalPlaces: 2),

                    TextEntry::make('notes')
                        ->label('Catatan')
                        ->placeholder('-')
                        ->columnSpanFull(),
                ]),

            Section::make('Item Invoice')
                ->icon(Heroicon::QueueList)
                ->iconColor('primary')
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('invoiceItems')
                        ->hiddenLabel()
                        ->columns([
                            'default' => 1,
                            'lg' => 4,
                        ])
                        ->schema([
                            TextEntry::make('item.name')
                                ->label('Item')
                                ->columnSpan([
                                    'default' => 1,
                                    'lg' => 2,
                                ]),

                            TextEntry::make('qty')
                                ->label('Qty')
                                ->numeric(),

                            TextEntry::make('price')
                                ->label('Harga')
                                ->money('IDR', decimalPlaces: 2),

                            TextEntry::make('subtotal')
                                ->label('Subtotal')
                                ->money('IDR', decimalPlaces: 2),
                        ]),
                ]),

            Section::make('Aktivitas')
                ->icon(Heroicon::Clock)
                ->iconColor('primary')
                ->columnSpanFull()
                ->schema([
                    Grid::make()
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
