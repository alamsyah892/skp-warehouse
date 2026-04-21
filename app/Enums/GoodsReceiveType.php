<?php

namespace App\Enums;

use Filament\Support\Icons\Heroicon;

enum GoodsReceiveType: int
{
    case PURCHASE_ORDER = 1;
    case MANUAL = 2;

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE_ORDER => __('goods-receive.type.purchase_order.label'),
            self::MANUAL => __('goods-receive.type.manual.label'),
        };
    }

    public function initial(): string
    {
        return match ($this) {
            self::PURCHASE_ORDER => 'BPB',
            self::MANUAL => 'BPB-N',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PURCHASE_ORDER => 'primary',
            self::MANUAL => 'gray',
        };
    }

    public function icon()
    {
        return match ($this) {
            self::PURCHASE_ORDER => Heroicon::OutlinedShoppingCart,
            self::MANUAL => Heroicon::OutlinedPencilSquare,
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

