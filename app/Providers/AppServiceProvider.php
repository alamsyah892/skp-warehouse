<?php

namespace App\Providers;

// use Filament\Actions\BulkActionGroup;
// use Filament\Actions\DeleteBulkAction;
// use Filament\Actions\ForceDeleteBulkAction;
// use Filament\Actions\RestoreBulkAction;
use Illuminate\Support\ServiceProvider;
// use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Table::configureUsing(function (Table $table): void {
            $table
                ->contentGrid([
                    'md' => 2,
                    'xl' => 3,
                ])
                ->toolbarActions([
                    // BulkActionGroup::make([
                    //     DeleteBulkAction::make(),
                    //     ForceDeleteBulkAction::make(),
                    //     RestoreBulkAction::make(),
                    // ]),
                ])
                ->defaultSort('id', 'desc')
                ->persistSortInSession()
                ->persistSearchInSession()
                ->persistFiltersInSession()
                ->deferFilters(false)
                // ->paginated([5, 10, 25, 50, 100])
                ->paginated([6, 12, 24, 48, 96])
                ->defaultPaginationPageOption(12)
                ->deferLoading()
            ;
        });
    }
}
