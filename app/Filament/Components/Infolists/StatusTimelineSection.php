<?php

namespace App\Filament\Components\Infolists;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class StatusTimelineSection
{
    public static function make(
        string $relation = 'statusLogs',
        string $title = 'Status Timeline',
    ): Section {
        return Section::make($title)
            ->icon(Heroicon::Clock)
            ->iconColor('primary')
            ->collapsible()
            ->compact()
            ->columnSpanFull()
            ->schema([
                RepeatableEntry::make($relation)
                    ->hiddenLabel()
                    ->schema([
                        TextEntry::make('to_status')
                            ->hiddenLabel()
                            ->icon(fn($state) => $state?->icon())
                            ->iconColor(fn($state) => $state?->color())
                            ->formatStateUsing(function ($state, $record) {
                                $status = $state?->label();
                                $user = $record->user?->name ?? 'System';
                                $date = $record->created_at->format('M d, Y');
                                $note = $record->note ? '<br>Note: ' . $record->note : '';

                                return __('common.log_format_with_date', [
                                    'date' => $date,
                                    'status' => $status,
                                    'user' => $user,
                                ]) . $note;
                            })
                            ->html()
                            ->color('gray'),
                    ])
                    ->contained(false),
            ]);
    }
}

