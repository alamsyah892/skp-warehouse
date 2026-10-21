<?php

namespace App\Services;

use App\Enums\PurchaseOrderTaxType;
use BackedEnum;
use Illuminate\Support\Collection;

class OrderCalculationService
{
    /**
     * Normalize items to a standard qty and price structure.
     *
     * @param  array<int, mixed>|Collection<int, mixed>  $items
     * @return array<int, array{qty: float, price: float}>
     */
    public static function normalizeSummaryItems(array|Collection $items): array
    {
        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item) || is_object($item))
            ->map(function (mixed $item): array {
                if (is_object($item)) {
                    return [
                        'qty' => max((float) ($item->qty ?? 0), 0.0),
                        'price' => max((float) ($item->price ?? 0), 0.0),
                    ];
                }

                return [
                    'qty' => max((float) ($item['qty'] ?? 0), 0.0),
                    'price' => max((float) ($item['price'] ?? 0), 0.0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Calculate the subtotal from a collection or array of items.
     *
     * @param  array<int, mixed>|Collection<int, mixed>  $items
     */
    public static function calculateSubtotal(array|Collection $items): float
    {
        $normalizedItems = self::normalizeSummaryItems($items);

        return round(
            collect($normalizedItems)->sum(
                fn (array $item): float => max((float) ($item['qty'] ?? 0) * (float) ($item['price'] ?? 0), 0.0)
            ),
            2
        );
    }

    /**
     * Predefined tax percentage options.
     *
     * @return array<int, string>
     */
    public static function getTaxPercentageOptions(): array
    {
        return collect([0, 10, 11, 12])
            ->mapWithKeys(fn (int $percentage): array => [
                $percentage => "{$percentage}%",
            ])
            ->all();
    }

    /**
     * Normalize tax type representation.
     */
    public static function normalizeTaxType(PurchaseOrderTaxType|BackedEnum|string|null $taxType): ?PurchaseOrderTaxType
    {
        if ($taxType instanceof PurchaseOrderTaxType) {
            return $taxType;
        }

        if (blank($taxType)) {
            return null;
        }

        if ($taxType instanceof BackedEnum) {
            return PurchaseOrderTaxType::tryFrom((string) $taxType->value);
        }

        return PurchaseOrderTaxType::tryFrom((string) $taxType);
    }

    /**
     * Calculate tax amount based on net subtotal and tax configuration.
     */
    public static function calculateTaxAmount(
        float|int|string|null $netSubtotal,
        PurchaseOrderTaxType|BackedEnum|string|null $taxType,
        float|int|string|null $taxPercentage,
    ): float {
        $taxableAmount = max(round((float) $netSubtotal, 2), 0.0);
        $rate = (float) $taxPercentage;
        $normalizedTaxType = self::normalizeTaxType($taxType);

        if ($taxableAmount <= 0.0 || $rate <= 0.0 || $normalizedTaxType === null) {
            return 0.0;
        }

        return match ($normalizedTaxType) {
            PurchaseOrderTaxType::EXCLUDE => ($taxableAmount * ($rate / 100)),
            PurchaseOrderTaxType::INCLUDE => ($taxableAmount - ($taxableAmount / (1 + ($rate / 100)))),
        };
    }

    /**
     * Calculate full summary totals including subtotal, discount, tax, and rounding.
     *
     * @param  array<int, mixed>|Collection<int, mixed>  $items
     * @return array{
     *     subtotal: float,
     *     discount: float,
     *     subtotal_after_discount: float,
     *     tax_base: float,
     *     tax_amount: float,
     *     tax: float,
     *     total_before_rounding: float,
     *     rounding: float,
     *     grand_total: float
     * }
     */
    public static function calculateSummary(
        array|Collection $items,
        float|int|string|null $discount = 0,
        PurchaseOrderTaxType|BackedEnum|string|null $taxType = null,
        float|int|string|null $taxPercentage = null,
        float|int|string|null $rounding = 0,
    ): array {
        $subtotal = self::calculateSubtotal($items);
        $discountAmount = min(max(round((float) $discount, 2), 0.0), $subtotal);
        $subtotalAfterDiscount = max(round($subtotal - $discountAmount, 2), 0.0);
        $normalizedTaxType = self::normalizeTaxType($taxType);
        $taxAmount = self::calculateTaxAmount($subtotalAfterDiscount, $normalizedTaxType, $taxPercentage);
        $taxBase = $normalizedTaxType === PurchaseOrderTaxType::INCLUDE
            ? max(round($subtotalAfterDiscount - $taxAmount, 2), 0.0)
            : $subtotalAfterDiscount;
        $totalBeforeRounding = $normalizedTaxType === PurchaseOrderTaxType::EXCLUDE
            ? round($taxBase + $taxAmount, 2)
            : $subtotalAfterDiscount;
        $roundingValue = round((float) $rounding, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'subtotal_after_discount' => $subtotalAfterDiscount,
            'tax_base' => $taxBase,
            'tax_amount' => $taxAmount,
            'tax' => $taxAmount,
            'total_before_rounding' => $totalBeforeRounding,
            'rounding' => $roundingValue,
            'grand_total' => max(round($totalBeforeRounding + $roundingValue, 2), 0.0),
        ];
    }

    /**
     * Backward-compatible alias for calculateSummary.
     *
     * @param  array<int, mixed>|Collection<int, mixed>  $items
     * @return array{
     *     subtotal: float,
     *     discount: float,
     *     subtotal_after_discount: float,
     *     tax_base: float,
     *     tax_amount: float,
     *     tax: float,
     *     total_before_rounding: float,
     *     rounding: float,
     *     grand_total: float
     * }
     */
    public static function calculateOrderSummary(
        array|Collection $items,
        float|int|string|null $discount = 0,
        PurchaseOrderTaxType|BackedEnum|string|null $taxType = null,
        float|int|string|null $taxPercentage = null,
        float|int|string|null $rounding = 0,
    ): array {
        return self::calculateSummary(
            $items,
            $discount,
            $taxType,
            $taxPercentage,
            $rounding,
        );
    }

    /**
     * Instance method to calculate summary when injected as dependency.
     *
     * @param  array<int, mixed>|Collection<int, mixed>  $items
     * @return array{
     *     subtotal: float,
     *     discount: float,
     *     subtotal_after_discount: float,
     *     tax_base: float,
     *     tax_amount: float,
     *     tax: float,
     *     total_before_rounding: float,
     *     rounding: float,
     *     grand_total: float
     * }
     */
    public function summary(
        array|Collection $items,
        float|int|string|null $discount = 0,
        PurchaseOrderTaxType|BackedEnum|string|null $taxType = null,
        float|int|string|null $taxPercentage = null,
        float|int|string|null $rounding = 0,
    ): array {
        return self::calculateSummary(
            $items,
            $discount,
            $taxType,
            $taxPercentage,
            $rounding,
        );
    }
}
