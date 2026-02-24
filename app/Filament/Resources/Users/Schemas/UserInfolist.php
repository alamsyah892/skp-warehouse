<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class UserInfolist
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
                                    Fieldset::make('User')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            Flex::make([
                                                Section::make()
                                                    ->contained(false)
                                                    ->schema([
                                                        ImageEntry::make('avatar_url')
                                                            ->hiddenLabel()
                                                            ->circular()
                                                            ->disk('public')
                                                            ->defaultImageUrl(
                                                                function ($record) {
                                                                    $name = urlencode($record->name);
                                                                    return url("https://ui-avatars.com/api/?name={$name}&background=random&color=fff");
                                                                }
                                                            )
                                                            ->extraImgAttributes([
                                                                'alt' => 'Image',
                                                                'loading' => 'lazy',
                                                            ])
                                                        ,
                                                    ])
                                                    ->grow(false)
                                                ,
                                                Section::make()
                                                    ->contained(false)
                                                    ->schema([
                                                        TextEntry::make('name'),
                                                        TextEntry::make('email'),
                                                    ])
                                                ,
                                            ])
                                                ->from('md')
                                                ->columnSpanFull()
                                            ,
                                        ])
                                    ,
                                    Fieldset::make('Related Data')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('roles.name')
                                                ->label(fn($record) => 'Roles (' . ($record->roles?->count() ?? 0) . ')')
                                                ->badge()
                                                ->columnSpanFull()
                                            ,
                                            TextEntry::make('warehouses.name')
                                                ->label(fn($record) => 'Warehouses (' . ($record->warehouses?->count() ?? 0) . ')')
                                                ->badge()
                                                ->columnSpanFull()
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
                                            TextEntry::make('email_verified_at')
                                                ->dateTime()
                                                ->placeholder('-')
                                                ->columnSpanFull()
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