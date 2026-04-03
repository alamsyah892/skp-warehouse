<?php

namespace App\Enums;

enum PurchaseOrderTaxType: string
{
    case INCLUDE = 'include';
    case EXCLUDE = 'exclude';

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
            ->mapWithKeys(fn (self $type): array => [
                $type->value => $type->label(),
            ])
            ->all();
    }
}
