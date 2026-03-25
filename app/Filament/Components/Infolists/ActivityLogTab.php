<?php

namespace App\Filament\Components\Infolists;

use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\RepeatableEntry;
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
                    ->label(__('common.log_activity.label'))
                    ->schema([
                        TextEntry::make('event')
                            ->formatStateUsing(function ($state, $record) {
                                $status = __('common.log_activity.' . $state . '.label');
                                $user = $record->causer?->name ?? 'System';
                                $date = $record->created_at->format('M d, Y');

                                return __('common.log_format_with_date', [
                                    'date' => $date,
                                    'status' => $status,
                                    'user' => $user,
                                ]);
                            })
                            ->color('gray')
                        ,

                        CodeEntry::make('properties')
                            ->visible(fn($state) => filled($state))
                            ->formatStateUsing(function ($state) {
                                return json_encode($state, JSON_PRETTY_PRINT);
                            })
                            ->columnSpanFull()
                        ,
                    ])
                    ->columnSpanFull()
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
