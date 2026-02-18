<?php

namespace App\Filament\Components\Tables;

use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;

class TimestampPanel
{
    public static function make(): Split
    {
        return Split::make([
            TextColumn::make('created_at')
                ->description("Created at: ", position: 'above')
                ->sortable()
                ->date(),

            TextColumn::make('updated_at')
                ->description("Updated at: ", position: 'above')
                ->sortable()
                ->date(),
        ]);
    }
}
