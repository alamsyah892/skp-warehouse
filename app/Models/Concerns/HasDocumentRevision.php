<?php

namespace App\Models\Concerns;

use BackedEnum;
use Illuminate\Support\Carbon;

trait HasDocumentRevision
{
    public function applyRevision(array &$data): void
    {
        $newInfo = $data['info'] ?? '';

        if ($newInfo === '' || !$this->hasWatchedFieldChanges($data)) {
            $data['info'] = $this->info;
            return;
        }

        $oldInfo = $this->info ?? '';

        // preg_match('/-Rev\.(\d+)$/', (string) $this->number, $numberMatch);
        // $lastNumberRev = $numberMatch[1] ?? 0;
        $lastNumberRev = $this->getCurrentRevision();

        $newRev = $lastNumberRev + 1;
        $revNumber = str_pad($newRev, 2, '0', STR_PAD_LEFT);

        $newLine = "Rev.{$revNumber} - {$newInfo}";

        $data['info'] = trim($oldInfo . "\n" . $newLine);

        $this->number = $this->incrementRevision();
    }

    public function hasWatchedFieldChanges(array $data): bool
    {
        foreach ($this->getWatchedFields() as $field) {
            $old = $this->getOriginal($field);
            $new = $data[$field] ?? null;

            if ($this->normalizeWatchedFieldValue($old, $field) !== $this->normalizeWatchedFieldValue($new, $field)) {
                return true;
            }
        }

        return $this->hasWatchedItemsChanges($data);
    }

    public function hasWatchedFieldChangesFromState(array $state): bool
    {
        return $this->hasWatchedFieldChanges($this->prepareWatchedDataFromState($state));
    }

    protected function prepareWatchedDataFromState(array $state): array
    {
        $data = [];

        foreach ($this->getWatchedFields() as $field) {
            $data[$field] = array_key_exists($field, $state)
                ? $state[$field]
                : $this->getAttribute($field);
        }

        $relation = $this->getRevisionItemsRelation();

        if ($relation) {
            $incomingItems = $state[$relation] ?? null;

            if (!is_array($incomingItems)) {
                $items = $this->relationLoaded($relation)
                    ? $this->{$relation}
                    : $this->{$relation}()->get();

                $incomingItems = $items
                    ->map(fn($item): array => $this->mapRevisionItem($item))
                    ->values()
                    ->toArray();
            }

            $data[$relation] = $incomingItems;
        }

        return $data;
    }

    protected function hasWatchedItemsChanges(array $data): bool
    {
        $relation = $this->getRevisionItemsRelation();

        if (!$relation) {
            return false;
        }

        $items = $this->relationLoaded($relation)
            ? $this->$relation
            : $this->$relation()->get();

        $existing = $items
            ->map(fn($item) => $this->mapRevisionItem($item))
            ->values()
            ->toArray();

        $incoming = collect($data[$relation] ?? [])
            ->map(fn($item) => $this->mapRevisionItemFromArray($item))
            ->values()
            ->toArray();

        $normalize = fn($items) => collect($items)
            ->sortBy(fn($i) => json_encode($i))
            ->values()
            ->toArray();

        return $normalize($existing) !== $normalize($incoming);
    }

    // hooks (override di model)

    protected function getWatchedFields(): array
    {
        return [];
    }

    protected function getRevisionItemsRelation(): ?string
    {
        return null;
    }

    protected function mapRevisionItem($item): array
    {
        return (array) $item;
    }

    protected function mapRevisionItemFromArray(array $item): array
    {
        return $item;
    }

    protected function normalizeWatchedFieldValue(mixed $value, ?string $field = null): mixed
    {
        if (
            is_string($field) &&
            str_ends_with($field, '_date') &&
            filled($value)
        ) {
            try {
                return Carbon::parse((string) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        if (
            is_string($field) &&
            method_exists($this, 'hasCast') &&
            $this->hasCast($field, ['int', 'integer', 'real', 'float', 'double', 'decimal'])
        ) {
            if (blank($value)) {
                return 0.0;
            }

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        if (is_null($value)) {
            return '';
        }

        return $value;
    }
}
