<?php

namespace App\Filament\Resources\CashBankTransactions\Schemas;

use App\Enums\TransactionType;
use App\Models\Bank;
use App\Models\CashBankTransaction;
use App\Models\Company;
use App\Models\Project;
use App\Rules\BelongsToCompany;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;

class CashBankTransactionForm
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
                    Select::make('company_id')
                        ->label('Perusahaan')
                        ->relationship('company', 'alias', fn($query) => $query->orderBy('alias')->orderBy('code'))
                        ->searchable(['alias', 'code', 'name'])
                        ->preload()
                        ->required()
                        ->live()
                        ->native(false)
                        ->helperText(fn(Get $get): string => Company::find($get('company_id'))?->name ?? '')
                        ->afterStateUpdated(function (callable $set): void {
                            $set('project_id', null);
                            $set('bank_id', null);
                        })
                        ->columnSpanFull()
                    ,
                    Select::make('bank_id')
                        ->label('Bank / Kas')
                        ->relationship(
                            'bank',
                            'name',
                            function ($query, Get $get): void {
                                $companyId = $get('company_id');

                                $query
                                    ->when(
                                        $companyId,
                                        fn($q) => $q->where('company_id', $companyId),
                                    )
                                    ->orderBy('name')
                                    ->orderBy('code')
                                ;
                            },
                        )
                        ->searchable(['name', 'code'])
                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} | {$record->account_number}")
                        ->preload()
                        ->required()
                        ->native(false)
                        ->disabled(fn(Get $get): bool => blank($get('company_id')))
                        ->dehydrated()
                        ->rules([
                            fn(Get $get): BelongsToCompany => new BelongsToCompany(
                                companyId: $get('company_id'),
                                modelClass: Bank::class,
                            ),
                        ])
                    ,
                    Select::make('type')
                        ->label('Jenis Transaksi')
                        ->options(TransactionType::options())
                        ->required()
                        ->native(false)
                    ,
                    Select::make('vendor_id')
                        ->label('Vendor')
                        ->relationship('vendor', 'name', fn($query) => $query->orderBy('name')->orderBy('code'))
                        ->searchable(['name', 'code'])
                        ->preload()
                        ->native(false)
                    ,
                    Textarea::make('description')
                        ->label('Keterangan')
                        ->columnSpanFull()
                    ,

                    TextInput::make('number')
                        ->label('Nomor Bukti')
                        ->required()
                        ->unique(ignoreRecord: true)
                        // ->default(fn(): string => CashBankTransaction::generateNumber())
                        // ->fontFamily(FontFamily::Mono)
                        ->maxLength(255)
                    ,
                    DatePicker::make('date')
                        ->label('Tanggal')
                        ->required()
                        ->default(now())
                        ->native(false)
                    ,
                    Select::make('project_id')
                        ->label('Proyek')
                        ->relationship(
                            'project',
                            'name',
                            function ($query, Get $get): void {
                                $companyId = $get('company_id');

                                $query
                                    ->when(
                                        $companyId,
                                        fn($q) => $q->whereHas(
                                            'companies',
                                            fn($qq) => $qq->where('companies.id', $companyId),
                                        ),
                                    )
                                    ->orderBy('name')
                                    ->orderBy('code')
                                ;
                            },
                        )
                        ->searchable(['name', 'code', 'po_code'])
                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} / {$record->po_code} | {$record->name}")
                        ->preload()
                        ->native(false)
                        ->disabled(fn(Get $get): bool => blank($get('company_id')))
                        ->dehydrated()
                        ->rules([
                            fn(Get $get): BelongsToCompany => new BelongsToCompany(
                                companyId: $get('company_id'),
                                modelClass: Project::class,
                                mode: 'many',
                            ),
                        ])
                    ,
                    TextInput::make('amount')
                        ->label('Jumlah')
                        ->numeric()
                        ->required()
                        // ->prefix('Rp')
                        ->minValue(0)
                        ->step(0.01)
                    ,
                    TextInput::make('check_number')
                        ->label('Nomor Cek')
                        ->maxLength(255)
                    ,
                ])
            ,
        ]);
    }
}
