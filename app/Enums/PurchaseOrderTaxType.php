<?php

namespace App\Enums;

enum PurchaseOrderTaxType: int
{
    // case INCLUDE = 'include';
    case INCLUDE = 1;
    // case EXCLUDE = 'exclude';
    case EXCLUDE = 2;

    public function label(): string
    {
        return match ($this) {
            self::INCLUDE => __('purchase-order.tax_type.include'),
            self::EXCLUDE => __('purchase-order.tax_type.exclude'),
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $type): array => [
                $type->value => $type->label(),
            ])
            ->all();
    }
}
