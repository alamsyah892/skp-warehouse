<?php

namespace App\Filament\Resources\GoodsIssues;

use App\Filament\Resources\GoodsIssues\Pages\CreateGoodsIssue;
use App\Filament\Resources\GoodsIssues\Pages\EditGoodsIssue;
use App\Filament\Resources\GoodsIssues\Pages\ListGoodsIssues;
use App\Filament\Resources\GoodsIssues\Pages\ViewGoodsIssue;
use App\Filament\Resources\GoodsIssues\Schemas\GoodsIssueForm;
use App\Filament\Resources\GoodsIssues\Schemas\GoodsIssueInfolist;
use App\Filament\Resources\GoodsIssues\Tables\GoodsIssuesTable;
use App\Models\GoodsIssue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class GoodsIssueResource extends Resource
{
    protected static ?string $model = GoodsIssue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLinkSlash;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::LinkSlash;
    public static ?int $navigationSort = 2;
    protected static string|UnitEnum|null $navigationGroup = 'Warehouse';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getModelLabel(): string
    {
        return __('goods-issue.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('goods-issue.model.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return GoodsIssueForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GoodsIssueInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GoodsIssuesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGoodsIssues::route('/'),
            'create' => CreateGoodsIssue::route('/create'),
            'view' => ViewGoodsIssue::route('/{record}'),
            'edit' => EditGoodsIssue::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withQuantitySummary()
            ->withCount(['goodsIssueItems'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with([
                'goodsIssueItems.item',
                'statusLogs' => fn($query) => $query->orderBy('id'),
                'statusLogs.user',
            ])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
