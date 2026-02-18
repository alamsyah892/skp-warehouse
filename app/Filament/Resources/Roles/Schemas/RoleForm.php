<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Tabs')->tabs([
                Tab::make('Data')
                    ->label(fn(?Model $record) => $record === null ? 'Form' : 'Data')
                    ->schema([
                        Flex::make([
                            Section::make('Role Information')->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                ,
                                TextInput::make('guard_name')
                                    ->visible(fn() => auth()->user()?->hasRole('Project Owner'))
                                    ->required()
                                    ->maxLength(255)
                                    ->default('web')
                                ,
                            ]),
                            Section::make('Related Data')->schema([
                                Select::make('users')
                                    ->relationship('users', 'name', function ($query) {
                                        $query->orderBy('name', 'asc');
                                        // If the user is not an owner, hide the role "Project Owner"
                                        if (!auth()->user()->hasRole('Project Owner')) {
                                            $query->whereDoesntHave('roles', function ($q) {
                                                $q->where('name', 'Project Owner');
                                            });
                                        }
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->multiple()
                                    ->placeholder('Select users')
                                ,
                            ]),
                        ])->from('md'),
                    ])
                ,
                Tab::make('Permissions')
                    ->schema([
                        CheckboxList::make('permissions')
                            ->label('Permission list')
                            ->relationship('permissions', 'name', function ($query) {
                                $query->orderBy('id', 'asc');
                            })
                            ->columns(4)
                            ->gridDirection('row')
                        ,
                    ])
                ,
                ActivityLogTab::make('Logs'),
            ])->columnSpanFull(),
        ]);
    }
}
