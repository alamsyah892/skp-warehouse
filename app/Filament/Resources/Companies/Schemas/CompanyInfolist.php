<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use App\Models\Company;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->tabs([
                Tab::make('Data')->schema([
                    Grid::make()
                        ->columns([
                            'default' => 1,
                            'lg' => 1,
                            'xl' => 1,
                            '2xl' => 4,
                        ])
                        ->schema([
                            Section::make() // left
                                ->columnSpan([
                                    '2xl' => 3,
                                ])
                                ->contained(false)
                                ->schema([
                                    Fieldset::make('Company')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('code')
                                                ->badge()
                                                ->color('info')
                                                ->fontFamily(FontFamily::Mono)
                                                ->size(TextSize::Large)
                                            ,

                                            TextEntry::make('alias')
                                                ->label('Business name / alias')
                                            ,
                                            TextEntry::make('name')
                                                ->label('Company name')
                                            ,
                                            TextEntry::make('description')
                                                ->placeholder('-')
                                                ->columnSpanFull()
                                            ,
                                            TextEntry::make('address')
                                                ->columnSpanFull()
                                            ,
                                            TextEntry::make('city'),
                                            TextEntry::make('post_code')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('contact_person')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('contact_person_position')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('phone')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('fax')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('email')
                                                ->label('Email address')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('website')
                                                ->placeholder('-')
                                            ,
                                            TextEntry::make('tax_number')
                                                ->placeholder('-')
                                            ,
                                        ])
                                    ,

                                    Fieldset::make('Bank')
                                        ->columnSpanFull()
                                        ->columns(1)
                                        ->schema([
                                            RepeatableEntry::make('banks')
                                                ->label(fn($record) => 'Banks (' . ($record->banks?->count() ?? 0) . ')')
                                                ->table([
                                                    TableColumn::make('Code'),
                                                    TableColumn::make('Name'),
                                                    TableColumn::make('Account number'),
                                                ])
                                                ->schema([
                                                    TextEntry::make('code')
                                                        ->fontFamily(FontFamily::Mono)
                                                    ,
                                                    TextEntry::make('name'),
                                                    TextEntry::make('account_number')->placeholder('-'),
                                                ])
                                            ,
                                        ])
                                    ,
                                ])
                            ,
                            Section::make() // right
                                ->contained(false)
                                ->schema([
                                    Fieldset::make('Configuration & Information')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('is_active')
                                                ->label('Status')
                                                ->formatStateUsing(fn($state) => Company::STATUS_LABELS[$state] ?? '-')
                                                ->badge()
                                                ->color(fn(bool $state) => $state == Company::STATUS_ACTIVE ? 'success' : 'danger')
                                            ,

                                            Grid::make()
                                                ->columnSpanFull()
                                                ->schema([
                                                    TextEntry::make('created_at')->date(),
                                                    TextEntry::make('updated_at')->date(),
                                                    TextEntry::make('deleted_at')->date()->visible(fn($state) => $state != null),
                                                ])
                                            ,
                                        ])
                                    ,

                                    Fieldset::make('Related Data')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('warehouses.name')
                                                ->label(fn($record) => 'Warehouses (' . ($record->warehouses?->count() ?? 0) . ')')
                                                ->badge()
                                                ->placeholder('-')
                                                ->columnSpanFull()
                                            ,
                                            TextEntry::make('divisions.name')
                                                ->label(fn($record) => 'Divisions (' . ($record->divisions?->count() ?? 0) . ')')
                                                ->badge()
                                                ->placeholder('-')
                                                ->columnSpanFull()
                                            ,
                                            TextEntry::make('projects.name')
                                                ->label(fn($record) => 'Projects (' . ($record->projects?->count() ?? 0) . ')')
                                                ->badge()
                                                ->placeholder('-')
                                                ->columnSpanFull()
                                            ,
                                        ])
                                    ,
                                ])
                            ,
                        ])
                    ,
                ]),
                ActivityLogTab::make('Logs'),
            ])->columnSpanFull(),
        ]);
    }
}
