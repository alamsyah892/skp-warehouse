<?php

namespace App\Filament\Resources\GoodsReceives;

use App\Filament\Resources\GoodsReceives\Pages\CreateGoodsReceive;
use App\Filament\Resources\GoodsReceives\Pages\EditGoodsReceive;
use App\Filament\Resources\GoodsReceives\Pages\ListGoodsReceives;
use App\Filament\Resources\GoodsReceives\Pages\ViewGoodsReceive;
use App\Filament\Resources\GoodsReceives\Schemas\GoodsReceiveForm;
use App\Filament\Resources\GoodsReceives\Schemas\GoodsReceiveInfolist;
use App\Filament\Resources\GoodsReceives\Tables\GoodsReceivesTable;
use App\Models\GoodsReceive;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class GoodsReceiveResource extends Resource
{
    protected static ?string $model = GoodsReceive::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;
    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::InboxArrowDown;
    public static ?int $navigationSort = 1;
    protected static string|UnitEnum|null $navigationGroup = 'Warehouse';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getModelLabel(): string
    {
        return __('goods-receive.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('goods-receive.model.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return GoodsReceiveForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GoodsReceiveInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GoodsReceivesTable::configure($table);
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
            'index' => ListGoodsReceives::route('/'),
            'create' => CreateGoodsReceive::route('/create'),
            'view' => ViewGoodsReceive::route('/{record}'),
            'edit' => EditGoodsReceive::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['goodsReceiveItems'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with([
                'purchaseOrder',
                'goodsReceiveItems.purchaseOrderItem.purchaseRequestItem.purchaseRequest',
                'goodsReceiveItems.item',
                'statusLogs' => fn($query) => $query->orderBy('id'),
                'statusLogs.user',
            ])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
