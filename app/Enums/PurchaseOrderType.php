<?php

namespace App\Enums;

enum PurchaseOrderType: int
{
    case RED = 1;
    case WHITE = 2;
    case WORK_ORDER = 3;

    public function label(): string
    {
        return match ($this) {
            self::RED => 'PO Merah',
            self::WHITE => 'PO Putih',
            self::WORK_ORDER => 'SPK',
        };
    }

    public function initial(): string
    {
        return match ($this) {
            self::RED => 'PO-M',
            self::WHITE => 'PO-P',
            self::WORK_ORDER => 'SPK',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $type): array => [
                $type->value => $type->label(),
            ])
            ->all()
        ;
    }
}
