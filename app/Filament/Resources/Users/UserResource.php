<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Users;
    public static ?int $navigationSort = 1;
    protected static string|UnitEnum|null $navigationGroup = 'User Management';

    protected static ?string $recordTitleAttribute = 'name';


    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        $query->with(['roles', 'warehouses']);

        // If the user is not an owner, hide the role "Project Owner"
        if (!auth()->user()->hasRole('Project Owner')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Project Owner');
            });
        }

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('Read User');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('Create User');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('Read User', $record);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('Update User', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('Delete User', $record);
    }
}
