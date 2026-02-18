<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Components\Infolists\ActivityLogTab;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Tabs')->tabs([
                Tab::make('Data')
                    ->label(fn(?Model $record) => $record === null ? 'Form' : 'Data')
                    ->schema([
                        Flex::make([
                            Section::make('User Information')->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                ,
                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                ,
                                DateTimePicker::make('email_verified_at')
                                    ->native(false)
                                    ->displayFormat('d/m/Y H:i:s')
                                    ->defaultFocusedDate(now())
                                ,
                            ]),
                            Section::make('Related Data')->schema([
                                Select::make('roles')
                                    ->relationship('roles', 'name', function ($query) {
                                        $query->orderBy('id', 'asc');
                                        // If the user is not an owner, hide the role "Project Owner"
                                        if (!auth()->user()->hasRole('Project Owner')) {
                                            $query->where('name', '!=', 'Project Owner');
                                        }
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->multiple()
                                    ->placeholder('Select roles')
                                ,
                                Select::make('warehouses')
                                    ->relationship('warehouses', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->multiple()
                                    ->placeholder('Select warehouses')
                                ,
                            ]),
                        ])->from('md'),
                    ])
                ,
                ActivityLogTab::make('Logs'),
            ])->columnSpanFull(),
        ]);
    }
}
