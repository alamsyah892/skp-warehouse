<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Resources\Projects\Schemas\ProjectInfolist;
use App\Filament\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Square3Stack3d;
    public static ?int $navigationSort = 4;
    protected static string|UnitEnum|null $navigationGroup = 'General';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProjectInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
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
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view' => ViewProject::route('/{record}'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $query
            ->withCount([
                'companies',
                'warehouses',
                'purchaseRequests',
                // 'purchaseRequestItems',
            ])
        ;

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        $query
            ->with([
                'companies' => fn($query) => $query->orderByDesc('alias'),
                'warehouses' => fn($query) => $query->orderByDesc('name'),
                'purchaseRequests' => fn($query) => $query->orderByDesc('id'),
                // 'purchaseRequestItems' => fn($query) => $query->orderByDesc('purchase_request_id'),
            ])
        ;

        return $query->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('Read Project');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('Create Project');
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('Read Project', $record);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('Update Project', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('Delete Project', $record);
    }
}
