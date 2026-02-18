<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class RoleInfolist
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
                                    Fieldset::make('Role')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('name'),
                                            TextEntry::make('guard_name')
                                                ->badge()
                                                ->grow(false)
                                                ->visible(fn() => auth()->user()?->hasRole('Project Owner'))
                                            ,
                                        ])
                                    ,

                                    Fieldset::make('Related Data')
                                        ->columnSpanFull()
                                        ->columns(2)
                                        ->schema([
                                            ImageEntry::make('users.avatar_url')
                                                ->label(fn($record) => 'Users (' . ($record->users?->count() ?? 0) . ')')
                                                ->limitedRemainingText()
                                                ->circular()
                                                ->stacked()
                                                ->disk('public')
                                                ->defaultImageUrl(fn($record) => $record->users?->count() ? url('avatars/ic_default_user.png') : false)
                                                ->extraImgAttributes([
                                                    'alt' => 'Image',
                                                    'loading' => 'lazy',
                                                ])
                                                ->columnSpanFull()
                                            ,
                                            TextEntry::make('users.name')
                                                ->hiddenLabel()
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
                                            Grid::make()
                                                ->columnSpanFull()
                                                ->schema([
                                                    TextEntry::make('created_at')->date(),
                                                    TextEntry::make('updated_at')->date(),
                                                    TextEntry::make('deleted_at')->date()->visible(fn($state) => $state != null),
                                                ])
                                            ,

                                            TextEntry::make('permissions.name')
                                                ->label('Permission')
                                                ->listWithLineBreaks()
                                                ->bulleted()
                                                ->columnSpanFull()
                                            ,
                                        ])
                                    ,
                                ])
                            ,
                        ])
                    ,
                ]),
                // Tab::make('Permissions')->schema([
                //     Grid::make()
                //         ->columns([
                //             'default' => 1,
                //             'lg' => 1,
                //             'xl' => 1,
                //             '2xl' => 4,
                //         ])
                //         ->schema([
                //             Section::make() // left
                //                 ->columnSpan([
                //                     '2xl' => 3,
                //                 ])
                //                 ->contained(false)
                //                 ->schema([
                //                     Fieldset::make('Permissions')
                //                         ->columnSpanFull()
                //                         ->columns(2)
                //                         ->schema([
                //                             TextEntry::make('permissions.name')
                //                                 ->hiddenLabel()
                //                                 ->listWithLineBreaks()
                //                                 ->bulleted()
                //                             ,

                //                         ])
                //                     ,
                //                 ])
                //             ,
                //         ])
                //     ,
                // ]),
                ActivityLogTab::make('Logs'),
            ])->columnSpanFull(),
        ]);
    }
}