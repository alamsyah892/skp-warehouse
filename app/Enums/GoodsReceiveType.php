<?php

namespace App\Enums;

use Filament\Support\Icons\Heroicon;

enum GoodsReceiveType: int
{
    case PURCHASE_ORDER = 1;
    case MANUAL = 2;
    case TRANSFER = 3;
    case CORRECTION = 4;

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE_ORDER => __('goods-receive.type.purchase_order.label'),
            self::MANUAL => __('goods-receive.type.manual.label'),
            self::TRANSFER => __('goods-receive.type.transfer.label'),
            self::CORRECTION => __('goods-receive.type.correction.label'),
        };
    }

    public function initial(): string
    {
        return match ($this) {
            self::PURCHASE_ORDER => 'BPB',
            self::MANUAL => 'BPB-N',
            self::TRANSFER => 'MM',
            self::CORRECTION => 'IMP',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PURCHASE_ORDER => 'primary',
            self::MANUAL => 'warning',
            self::TRANSFER => 'info',
            self::CORRECTION => 'success',
        };
    }

    public function icon()
    {
        return match ($this) {
            self::PURCHASE_ORDER => Heroicon::OutlinedTruck,
            self::MANUAL => Heroicon::OutlinedPlus,
            self::TRANSFER => Heroicon::OutlinedShare,
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

