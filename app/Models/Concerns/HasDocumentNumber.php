<?php

namespace App\Models\Concerns;

use RuntimeException;
use Illuminate\Support\Facades\DB;

trait HasDocumentNumber
{
    protected static function generateNumber($record): string
    {
        return DB::transaction(function () use ($record) {
            $year = now()->format('y');
            $month = now()->format('m');
            $project = $record->project ?? $record->project()->first();
            $division = $record->division ?? $record->division()->first();

            if (! $project || ! $division) {
                throw new RuntimeException('Document number cannot be generated without project and division.');
            }

            $prefix = sprintf(
                '%s/%s/%s/%s/%s', // '%s/%s/%s/%s%s%s%s',
                    $record::MODEL_ALIAS, // self::MODEL_ALIAS,
                $year,
                $month,
                // $record->warehouse->code,
                // $record->company->code,
                $project->po_code, // $record->project->code,
                $division->code,
            );

            $last = $record::withTrashed()
                ->where('number', 'like', "{$prefix}/%") // static::where('number', 'like', "{$prefix}/%")
                ->lockForUpdate()
                ->orderByDesc('number') // ->latest('number')
                ->value('number');

            $lastSequence = 0;

            if ($last && preg_match('/\/(\d{3})(?:-Rev\.\d+)?$/', $last, $match)) {
                $lastSequence = (int) $match[1];
            }

            $sequence = str_pad($lastSequence + 1, 3, '0', STR_PAD_LEFT);

            return "{$prefix}/{$sequence}";
        });
    }

    public function incrementRevision(): string
    {
        $rev = $this->getCurrentRevision() + 1;

        $base = preg_replace('/-Rev\.\d+$/', '', (string) $this->number);

        return sprintf('%s-Rev.%02d', $base, $rev);
    }

    public function getCurrentRevision(): int
    {
        if (preg_match('/-Rev\.(\d+)$/', (string) $this->number, $match)) {
            return (int) $match[1];
        }

        return 0;
    }
}
