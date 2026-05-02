<?php

namespace App\Enums;

use Filament\Support\Icons\Heroicon;

enum GoodsReceiveType: int
{
    case PURCHASE_ORDER = 1;
    case MANUAL = 2;
    case CORRECTION = 3;

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE_ORDER => __('goods-receive.type.purchase_order.label'),
            self::MANUAL => __('goods-receive.type.manual.label'),
            self::CORRECTION => __('goods-receive.type.correction.label'),
        };
    }

    public function initial(): string
    {
        return match ($this) {
            self::PURCHASE_ORDER => 'BPB',
            self::MANUAL => 'BPB-N',
            self::CORRECTION => 'IMP',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PURCHASE_ORDER => 'primary',
            self::MANUAL => 'warning',
            self::CORRECTION => 'success',
        };
    }

    public function icon()
    {
        return match ($this) {
            self::PURCHASE_ORDER => Heroicon::OutlinedTruck,
            self::MANUAL => Heroicon::OutlinedPlus,
            self::CORRECTION => Heroicon::OutlinedPencilSquare,
        };
    }

    public static function options(): array
    {
        static $cache = null;

        return $cache ??= collect(self::cases())
            ->mapWithKeys(fn(self $type): array => [
                (string) $type->value => $type->label(),
            ])
            ->toArray();
    }
}

