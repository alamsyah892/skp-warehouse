<?php

namespace App\Enums;

use Filament\Support\Icons\Heroicon;

enum TransactionType: int
{
    case Receipt = 1;
    case Payment = 2;

    public function label(): string
    {
        return match ($this) {
            self::Receipt => 'Penerimaan',
            self::Payment => 'Pengeluaran',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Receipt => 'success',
            self::Payment => 'danger',
        };
    }

    public function icon(): Heroicon
    {
        return match ($this) {
            self::Receipt => Heroicon::OutlinedArrowDownCircle,
            self::Payment => Heroicon::OutlinedArrowUpCircle,
        };
    }

    public static function options(): array
    {
        static $cache = null;

        return $cache ??= collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [
                (string) $type->value => $type->label(),
            ])
            ->toArray();
    }
}
