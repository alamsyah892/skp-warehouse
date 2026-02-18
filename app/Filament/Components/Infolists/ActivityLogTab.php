<?php

namespace App\Filament\Components\Infolists;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class ActivityLogTab
{
    public static function make(string $label = 'Logs'): Tab
    {
        return Tab::make($label)
            ->icon(Heroicon::OutlinedClock)
            ->hidden(fn(?Model $record) => $record === null)
            ->schema([
                RepeatableEntry::make('activityLogs')
                    ->table([
                        TableColumn::make('Event'),
                        TableColumn::make('Time'),
                    ])
                    ->schema([
                        TextEntry::make('event')
                            ->formatStateUsing(
                                fn($state, $record) =>
                                ucfirst("{$state} by " . ($record->causer?->name ?? 'System'))
                            )
                        ,
                        TextEntry::make('created_at')
                            ->since()
                            ->color('gray')
                        ,
                    ])
                ,
                EmptyState::make('No activity yet')
                    ->description('No activity has been recorded yet.')
                    ->icon(Heroicon::Clock)
                    ->visible(fn(?Model $record) => $record && $record->activityLogs()->doesntExist())
                    ->contained(false)
                ,
            ])
        ;
    }
}
