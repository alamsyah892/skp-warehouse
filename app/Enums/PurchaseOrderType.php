<?php

namespace App\Enums;

use Filament\Support\Icons\Heroicon;

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

    public function color(): string
    {
        return match ($this) {
            self::RED => 'danger',
            self::WHITE => 'gray',
            self::WORK_ORDER => 'info',
        };
    }

    public function icon()
    {
        return match ($this) {
            self::RED => Heroicon::OutlinedDocument,
            self::WHITE => Heroicon::OutlinedDocument,
            self::WORK_ORDER => Heroicon::OutlinedDocument,
        };
    }

    /**
     * Suffix used to resolve the print view of this purchase order type,
     * e.g. "red" for the purchase-order-red view.
     */
    public function viewSuffix(): string
    {
        return match ($this) {
            self::RED => 'red',
            self::WHITE => 'white',
            self::WORK_ORDER => 'work_order',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [
                $type->value => $type->label(),
            ])
            ->all();
    }
}
