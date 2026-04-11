<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderTaxType;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\HasDocumentNumber;
use App\Models\Concerns\HasDocumentRevision;
use App\Models\Concerns\HasStateMachine;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PurchaseOrder extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseOrderFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString, HasDocumentNumber, HasStateMachine, HasDocumentRevision;


    protected $fillable = [
        'vendor_id',
        'company_id',
        'warehouse_id',
        'warehouse_address_id',
        'division_id',
        'project_id',
        'user_id',

        'type',

        'number',
        'description',
        'memo',
        'termin',
        'delivery_info',
        'notes',

        'info',

        'status',

        'discount',
        'tax_type',
        'tax_percentage',
        'tax',
        'tax_description',
        'rounding',
    ];

    protected array $defaultEmptyStringFields = [
        'description',
        'memo',
        'termin',
        'delivery_info',
        'notes',

        'info',

        'tax_description',
    ];

    protected $casts = [
        'status' => PurchaseOrderStatus::class,

        'discount' => 'decimal:2',
        'tax_type' => PurchaseOrderTaxType::class,
        // 'tax_percentage' => 'decimal:2',
        'tax' => 'decimal:2',
        'rounding' => 'decimal:2',
    ];


    public const MODEL_ALIAS = 'PO';
    public const TYPE_PURCHASE_ORDER = 1;
    public const DEFAULT_TAX_PERCENTAGE = 11;


    protected static function booted(): void
    {
        static::addGlobalScope('user_warehouses', function ($builder) {
            if ($user = auth()->user()) {
                $userWarehouseIds = $user->warehouses()->pluck('warehouses.id');

                if ($userWarehouseIds->isNotEmpty()) {
                    $builder->whereIn('warehouse_id', $userWarehouseIds);
                }
            }
        });

        static::creating(function (self $record) {
            $record->user_id = auth()->id();
            $record->type = self::TYPE_PURCHASE_ORDER;

            $record->loadMissing([
                'division',
                'project',
            ]);
            $record->number = self::generateNumber($record);

            $record->status = PurchaseOrderStatus::DRAFT;
        });

        static::created(function (self $record) {
            $record->setStatusLog(PurchaseOrderStatus::DRAFT);
        });
    }


    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseAddress(): BelongsTo
    {
        return $this->belongsTo(WarehouseAddress::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('sort');
    }

    public function purchaseRequestItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->with('purchaseRequestItem');
    }

    public function purchaseRequests(): BelongsToMany
    {
        return $this->belongsToMany(PurchaseRequest::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PurchaseOrderStatusLog::class);
    }


    public function getSubtotalAmount(): float
    {
        return (float) $this->purchaseOrderItems->sum(
            fn(PurchaseOrderItem $item): float => $item->getLineTotalAmount()
        );
    }

    public function getNetSubtotalAmount(): float
    {
        return max($this->getSubtotalAmount() - (float) $this->discount, 0.0);
    }

    public function getTaxAmount(): float
    {
        return self::calculateTaxAmount(
            $this->getNetSubtotalAmount(),
            $this->tax_type,
            $this->tax_percentage,
        );
    }

    public function getGrandTotalAmount(): float
    {
        return self::calculateGrandTotalFromNetSubtotal(
            $this->getNetSubtotalAmount(),
            $this->tax_type,
            $this->tax_percentage,
            $this->rounding,
        );
    }


    protected function getStatusField(): string
    {
        return 'status';
    }

    protected function getStatusEnumClass(): string
    {
        return PurchaseOrderStatus::class;
    }

    protected function canUserTransition($newStatus, $user, array $flow): bool
    {
        if (
            $this->hasStatus(PurchaseOrderStatus::DRAFT) &&
            in_array($newStatus, [
                PurchaseOrderStatus::CANCELED,
                PurchaseOrderStatus::ORDERED
            ], true) &&
            $this->user_id === $user->id
        ) {
            return true;
        }

        return $user->hasAnyRole($flow[$newStatus->value] ?? []);
    }


    public function setStatusLog($newStatus, $oldStatus = null, string $note = '')
    {
        $newStatus = $this->normalizeStatus($newStatus);
        $oldStatus = $this->normalizeStatus($oldStatus);

        $this->statusLogs()->create([
            'user_id' => auth()->id(),
            'from_status' => $oldStatus?->value,
            'to_status' => $newStatus->value,
            'note' => $note,
        ]);
    }


    public function hasStatus(PurchaseOrderStatus $status): bool
    {
        return $this->status === $status;
    }


    public static function getCompatiblePurchaseRequestsQuery(?array $header = null): Builder
    {
        return PurchaseRequest::query()
            ->when($header, function (Builder $query) use ($header): void {
                $query
                    ->where('warehouse_id', $header['warehouse_id'])
                    ->where('company_id', $header['company_id'])
                    ->where('division_id', $header['division_id'])
                    ->where('project_id', $header['project_id'])
                ;
            })
        ;
    }

    public static function getCompatiblePurchaseRequestItemsQuery(array $purchaseRequestIds = []): Builder
    {
        return PurchaseRequestItem::query()
            ->with([
                'item:id,code,name,unit',
                'purchaseRequest:id,number,warehouse_id,company_id,division_id,project_id,status',
            ])
            ->whereHas('purchaseRequest', function (Builder $query) use ($purchaseRequestIds) {
                if (blank($purchaseRequestIds)) {
                    return;
                }

                $query->whereIn('id', $purchaseRequestIds);
            })
        ;
    }

    public static function normalizePurchaseRequestIds(array $purchaseRequestIds): array
    {
        return collect($purchaseRequestIds)
            ->flatten()
            ->filter()
            ->map(fn($id): int => (int) $id)
            ->unique()
            ->values()
            ->all()
        ;
    }

    public static function extractHeaderFromPurchaseRequests(array $purchaseRequestIds): ?array
    {
        $ids = self::normalizePurchaseRequestIds($purchaseRequestIds);

        if (blank($ids)) {
            return null;
        }

        $rows = PurchaseRequest::query()
            ->whereIn('id', $ids)
            ->get([
                'id',
                'warehouse_id',
                'company_id',
                'division_id',
                'project_id',
            ])
        ;

        if ($rows->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'purchaseRequests' => __('purchase-order.validation.source_purchase_request_not_found'),
            ]);
        }

        $first = $rows->first();

        $isCompatible = $rows->every(function (PurchaseRequest $purchaseRequest) use ($first): bool {
            return $purchaseRequest->warehouse_id === $first->warehouse_id
                && $purchaseRequest->company_id === $first->company_id
                && $purchaseRequest->division_id === $first->division_id
                && $purchaseRequest->project_id === $first->project_id
            ;
        });

        if (!$isCompatible) {
            throw ValidationException::withMessages([
                'purchaseRequests' => __('purchase-order.validation.incompatible_purchase_requests'),
            ]);
        }

        return [
            'warehouse_id' => $first->warehouse_id,
            'company_id' => $first->company_id,
            'division_id' => $first->division_id,
            'project_id' => $first->project_id,
        ];
    }

    public static function extractHeaderFromPurchaseRequestItems(array $items): ?array
    {
        $ids = collect($items)
            ->pluck('purchase_request_item_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
        ;

        if ($ids->isEmpty()) {
            return null;
        }

        $rows = PurchaseRequestItem::query()
            ->whereIn('id', $ids)
            ->with('purchaseRequest:id,warehouse_id,company_id,division_id,project_id,warehouse_address_id')
            ->get()
        ;

        if ($rows->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'purchaseOrderItems' => __('purchase-order.validation.source_item_not_found'),
            ]);
        }

        $first = $rows->first()->purchaseRequest;

        $isCompatible = $rows->every(function (PurchaseRequestItem $item) use ($first): bool {
            return $item->purchaseRequest
                && $item->purchaseRequest->warehouse_id === $first->warehouse_id
                && $item->purchaseRequest->company_id === $first->company_id
                && $item->purchaseRequest->division_id === $first->division_id
                && $item->purchaseRequest->project_id === $first->project_id
            ;
        });

        if (!$isCompatible) {
            throw ValidationException::withMessages([
                'purchaseOrderItems' => __('purchase-order.validation.incompatible_headers'),
            ]);
        }

        return [
            'warehouse_id' => $first->warehouse_id,
            'company_id' => $first->company_id,
            'division_id' => $first->division_id,
            'project_id' => $first->project_id,
        ];
    }

    public static function validateAllocationQuantities(array $items, ?int $currentPurchaseOrderId = null): void
    {
        foreach ($items as $index => $item) {
            $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);

            if ($purchaseRequestItemId <= 0) {
                continue;
            }

            $purchaseRequestItem = PurchaseRequestItem::query()->find($purchaseRequestItemId);

            if (!$purchaseRequestItem) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.purchase_request_item_id" => __('purchase-order.validation.source_item_not_found'),
                ]);
            }

            $remaining = $purchaseRequestItem->getRemainingQty($currentPurchaseOrderId);

            if ($qty > $remaining) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.qty" => __('purchase-order.validation.qty_exceeded', [
                        'remaining' => number_format($remaining, 2),
                    ]),
                ]);
            }
        }
    }

    public static function validateItemsBelongToPurchaseRequests(array $items, array $purchaseRequestIds): void
    {
        $purchaseRequestIds = self::normalizePurchaseRequestIds($purchaseRequestIds);

        foreach ($items as $index => $item) {
            $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);

            if ($purchaseRequestItemId <= 0) {
                continue;
            }

            $purchaseRequestItem = PurchaseRequestItem::query()
                ->select(['id', 'purchase_request_id'])
                ->find($purchaseRequestItemId)
            ;

            if (!$purchaseRequestItem) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.purchase_request_item_id" => __('purchase-order.validation.source_item_not_found'),
                ]);
            }

            if (!in_array($purchaseRequestItem->purchase_request_id, $purchaseRequestIds, true)) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.purchase_request_item_id" => __('purchase-order.validation.source_item_not_selected_pr'),
                ]);
            }
        }
    }

    public static function validateManualItems(array $items): void
    {
        foreach ($items as $index => $item) {
            $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);
            $itemId = (int) ($item['item_id'] ?? 0);

            if ($purchaseRequestItemId > 0) {
                continue;
            }

            if ($itemId <= 0) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.item_id" => __('validation.required'),
                ]);
            }

            $manualItem = Item::query()
                ->whereKey($itemId)
                ->whereHas('category', fn(Builder $query): Builder => $query->where('allow_po', true))
                ->exists();

            if (!$manualItem) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.item_id" => __('validation.exists', ['attribute' => 'item']),
                ]);
            }
        }
    }

    public static function syncHeaderFromPurchaseRequests(array &$data): void
    {
        $header = self::extractHeaderFromPurchaseRequests($data['purchaseRequests'] ?? []);

        if (!$header) {
            // Jangan reset ke null jika header tidak ditemukan (PR kosong)
            // Ini agar nilai dari Opsi 2 (input manual) tetap terjaga
            return;
        }

        $data['warehouse_id'] = $header['warehouse_id'];
        $data['company_id'] = $header['company_id'];
        $data['division_id'] = $header['division_id'];
        $data['project_id'] = $header['project_id'];
    }

    public static function syncHeaderFromPurchaseRequestItems(array &$data): void
    {
        $items = $data['purchaseOrderItems'] ?? [];
        $header = self::extractHeaderFromPurchaseRequestItems($items);

        if (!$header) {
            return;
        }

        $data['warehouse_id'] = $header['warehouse_id'];
        $data['company_id'] = $header['company_id'];
        $data['division_id'] = $header['division_id'];
        $data['project_id'] = $header['project_id'];
    }

    public static function syncPurchaseOrderItemsFromPurchaseRequestItems(array &$data): void
    {
        $items = collect($data['purchaseOrderItems'] ?? [])
            ->map(function (array $item): array {
                $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);

                if ($purchaseRequestItemId <= 0) {
                    return $item;
                }

                $purchaseRequestItem = PurchaseRequestItem::query()
                    ->select(['id', 'item_id', 'discount'])
                    ->find($purchaseRequestItemId);

                if (!$purchaseRequestItem) {
                    return $item;
                }

                $item['item_id'] = $purchaseRequestItem->item_id;

                return $item;
            })
            ->values()
            ->all()
        ;

        $data['purchaseOrderItems'] = $items;
    }


    public static function calculateItemTotal(array $item): float
    {
        $grossAmount = (float) ($item['qty'] ?? 0) * (float) ($item['price'] ?? 0);
        $discountAmount = (float) ($item['discount'] ?? 0);

        return max($grossAmount - $discountAmount, 0.0);
    }

    public static function getTaxPercentageOptions(): array
    {
        return collect([10, 11, 12])
            ->mapWithKeys(fn(int $percentage): array => [
                (string) $percentage => "{$percentage}%",
            ])
            ->all()
        ;
    }

    public static function calculateTaxAmount(
        float|int|string|null $netSubtotal,
        PurchaseOrderTaxType|string|null $taxType,
        float|int|string|null $taxPercentage,
    ): float {
        $taxableAmount = max((float) $netSubtotal, 0.0);
        $rate = (float) $taxPercentage;
        $normalizedTaxType = self::normalizeTaxType($taxType);

        if ($taxableAmount <= 0 || $rate <= 0 || !$normalizedTaxType) {
            return 0.0;
        }

        // Aturan Khusus Indonesia: Include PPN 12% menggunakan DPP Nilai Lain (11/12)
        if ($normalizedTaxType === PurchaseOrderTaxType::INCLUDE && $rate === 12.0) {
            $dpp = round(($taxableAmount * 11) / 12, 0);
            return round($dpp * 0.12, 0);
        }

        return match ($normalizedTaxType) {
            PurchaseOrderTaxType::EXCLUDE => round($taxableAmount * ($rate / 100), 0),
            PurchaseOrderTaxType::INCLUDE => round($taxableAmount - ($taxableAmount / (1 + ($rate / 100))), 0),
        };
    }

    public static function calculateSubtotalTax(
        array $items,
        float|int|string|null $discount = 0,
        PurchaseOrderTaxType|string|null $taxType = null,
        float|int|string|null $taxPercentage = null,
    ): float {
        $breakdown = self::calculateOrderBreakdown(
            $items,
            $discount,
            $taxType,
            $taxPercentage,
        );

        return (float) ($breakdown['summary']['tax_amount'] ?? 0.0);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{
     *     summary: array{
     *         gross_subtotal: float,
     *         discount_total: float,
     *         gross_after_discount: float,
     *         tax_base: float,
     *         tax_amount: float,
     *         before_rounding: float,
     *         rounding: float,
     *         grand_total: float
     *     },
     *     lines: array<int|string, array{
     *         key: int|string,
     *         gross_subtotal: float,
     *         item_discount: float,
     *         allocated_order_discount: float,
     *         discount_total: float,
     *         gross_after_discount: float,
     *         tax_base: float,
     *         tax_amount: float,
     *         total: float
     *     }>
     * }
     */
    public static function calculateOrderBreakdown(
        array $items,
        float|int|string|null $orderDiscount = 0,
        PurchaseOrderTaxType|string|null $taxType = null,
        float|int|string|null $taxPercentage = null,
        float|int|string|null $rounding = 0,
    ): array {
        $normalizedTaxType = self::normalizeTaxType($taxType);
        $normalizedItems = collect($items)
            ->map(function (array $item, int|string $index): array {
                $grossSubtotal = round(
                    (float) ($item['qty'] ?? 0) * (float) ($item['price'] ?? 0),
                    2,
                );
                $itemDiscount = round(min(max((float) ($item['discount'] ?? 0), 0.0), $grossSubtotal), 2);

                return [
                    'key' => $item['line_key'] ?? $item['id'] ?? $item['purchase_request_item_id'] ?? $index,
                    'gross_subtotal' => $grossSubtotal,
                    'item_discount' => $itemDiscount,
                    'line_subtotal' => max(round($grossSubtotal - $itemDiscount, 2), 0.0),
                ];
            })
            ->values()
        ;

        $orderDiscount = max(round((float) $orderDiscount, 2), 0.0);
        $lineSubtotals = $normalizedItems->pluck('line_subtotal')->all();
        $allocatedOrderDiscounts = self::allocateAmountAcrossLines($orderDiscount, $lineSubtotals, 2);

        $lines = $normalizedItems
            ->map(function (array $item, int $index) use ($allocatedOrderDiscounts): array {
                $allocatedOrderDiscount = $allocatedOrderDiscounts[$index] ?? 0.0;
                $grossAfterDiscount = max(round($item['line_subtotal'] - $allocatedOrderDiscount, 2), 0.0);

                return [
                    'key' => $item['key'],
                    'gross_subtotal' => $item['gross_subtotal'],
                    'item_discount' => $item['item_discount'],
                    'allocated_order_discount' => $allocatedOrderDiscount,
                    'discount_total' => round($item['item_discount'] + $allocatedOrderDiscount, 2),
                    'gross_after_discount' => $grossAfterDiscount,
                    'tax_base' => 0.0,
                    'tax_amount' => 0.0,
                    'total' => 0.0,
                ];
            })
            ->values()
        ;

        $grossSubtotal = round((float) $lines->sum('gross_subtotal'), 2);
        $discountTotal = round((float) $lines->sum('discount_total'), 2);
        $grossAfterDiscount = round((float) $lines->sum('gross_after_discount'), 2);

        $rate = self::resolveIndonesianVatRate($taxPercentage);
        $totalTaxAmount = self::calculateTaxAmount($grossAfterDiscount, $normalizedTaxType, $rate);

        $exactLineTaxes = $lines
            ->map(function (array $line) use ($normalizedTaxType, $rate): float {
                if ($normalizedTaxType === null || $rate <= 0 || $line['gross_after_discount'] <= 0) {
                    return 0.0;
                }

                // Gunakan formula 11/12 untuk pajak baris jika Include 12%
                if ($normalizedTaxType === PurchaseOrderTaxType::INCLUDE && $rate === 12.0) {
                    $dpp = ($line['gross_after_discount'] * 11) / 12;
                    return $dpp * 0.12;
                }

                return match ($normalizedTaxType) {
                    PurchaseOrderTaxType::EXCLUDE => $line['gross_after_discount'] * ($rate / 100),
                    PurchaseOrderTaxType::INCLUDE => $line['gross_after_discount'] - ($line['gross_after_discount'] / (1 + ($rate / 100))),
                };
            })
            ->all()
        ;

        $allocatedTaxes = self::allocateRoundedAmountAcrossLines($totalTaxAmount, $exactLineTaxes, 0);

        $lines = $lines
            ->map(function (array $line, int $index) use ($allocatedTaxes, $normalizedTaxType, $rate): array {
                $taxAmount = $allocatedTaxes[$index] ?? 0.0;

                if ($normalizedTaxType === PurchaseOrderTaxType::INCLUDE && $rate === 12.0) {
                    // Skema Include 12%: DPP dihitung dari 11/12 Nilai Setelah Diskon
                    $taxBase = round(($line['gross_after_discount'] * 11) / 12, 2);
                    $total = $line['gross_after_discount'];
                } else {
                    // Skema Normal
                    $taxBase = $normalizedTaxType === PurchaseOrderTaxType::INCLUDE
                        ? max(round($line['gross_after_discount'] - $taxAmount, 2), 0.0)
                        : $line['gross_after_discount'];
                    $total = $normalizedTaxType === PurchaseOrderTaxType::EXCLUDE
                        ? round($line['gross_after_discount'] + $taxAmount, 2)
                        : $line['gross_after_discount'];
                }

                $line['tax_base'] = $taxBase;
                $line['tax_amount'] = $taxAmount;
                $line['total'] = $total;

                return $line;
            })
            ->keyBy('key')
            ->all()
        ;

        $taxBaseAmount = round((float) collect($lines)->sum('tax_base'), 2);
        $beforeRounding = round((float) collect($lines)->sum('total'), 2);
        $rounding = round((float) $rounding, 2);

        return [
            'summary' => [
                'gross_subtotal' => $grossSubtotal,
                'discount_total' => $discountTotal,
                'gross_after_discount' => $grossAfterDiscount,
                'tax_base' => $taxBaseAmount,
                'tax_amount' => round((float) collect($lines)->sum('tax_amount'), 2),
                'before_rounding' => $beforeRounding,
                'rounding' => $rounding,
                'grand_total' => max(round($beforeRounding + $rounding, 2), 0.0),
            ],
            'lines' => $lines,
        ];
    }

    public static function resolveIndonesianVatRate(float|int|string|null $taxPercentage): float
    {
        // Mengembalikan nilai persentase apa adanya setelah dibulatkan
        return round(max((float) $taxPercentage, 0.0), 2);
    }

    /**
     * @param  array<int, float|int|string>  $weights
     * @return array<int, float>
     */
    protected static function allocateAmountAcrossLines(
        float|int|string|null $amount,
        array $weights,
        int $precision = 2,
    ): array {
        $normalizedAmount = max(round((float) $amount, $precision), 0.0);
        $scale = 10 ** $precision;
        $targetUnits = (int) round($normalizedAmount * $scale);
        $weightUnits = array_map(
            fn($weight): int => max((int) round(max((float) $weight, 0.0) * $scale), 0),
            $weights,
        );
        $totalWeightUnits = array_sum($weightUnits);

        if ($targetUnits <= 0 || $totalWeightUnits <= 0 || empty($weights)) {
            return array_fill(0, count($weights), 0.0);
        }

        $allocatedUnits = [];
        $remainders = [];
        $usedUnits = 0;

        foreach ($weightUnits as $index => $weightUnit) {
            if ($weightUnit <= 0) {
                $allocatedUnits[$index] = 0;
                $remainders[$index] = -INF;

                continue;
            }

            $exactUnits = ($weightUnit / $totalWeightUnits) * $targetUnits;
            $floorUnits = min((int) floor($exactUnits), $weightUnit);

            $allocatedUnits[$index] = $floorUnits;
            $remainders[$index] = $exactUnits - $floorUnits;
            $usedUnits += $floorUnits;
        }

        $remainingUnits = max($targetUnits - $usedUnits, 0);

        while ($remainingUnits > 0) {
            $nextIndex = null;
            $nextRemainder = -INF;

            foreach ($remainders as $index => $remainder) {
                if (($allocatedUnits[$index] ?? 0) >= ($weightUnits[$index] ?? 0)) {
                    continue;
                }

                if ($remainder > $nextRemainder) {
                    $nextIndex = $index;
                    $nextRemainder = $remainder;
                }
            }

            if ($nextIndex === null) {
                break;
            }

            $allocatedUnits[$nextIndex]++;
            $remainingUnits--;
        }

        return array_map(
            fn(int $units): float => round($units / $scale, $precision),
            $allocatedUnits,
        );
    }

    /**
     * @param  array<int, float|int|string>  $exactAmounts
     * @return array<int, float>
     */
    protected static function allocateRoundedAmountAcrossLines(
        float|int|string|null $roundedTotal,
        array $exactAmounts,
        int $precision = 0,
    ): array {
        $normalizedTotal = max(round((float) $roundedTotal, $precision), 0.0);
        $scale = 10 ** $precision;
        $targetUnits = (int) round($normalizedTotal * $scale);

        if ($targetUnits <= 0 || empty($exactAmounts)) {
            return array_fill(0, count($exactAmounts), 0.0);
        }

        $allocatedUnits = [];
        $remainders = [];
        $usedUnits = 0;

        foreach ($exactAmounts as $index => $exactAmount) {
            $normalizedAmount = max((float) $exactAmount, 0.0);
            $exactUnits = $normalizedAmount * $scale;
            $floorUnits = (int) floor($exactUnits);

            $allocatedUnits[$index] = $floorUnits;
            $remainders[$index] = $exactUnits - $floorUnits;
            $usedUnits += $floorUnits;
        }

        $remainingUnits = max($targetUnits - $usedUnits, 0);

        while ($remainingUnits > 0) {
            $nextIndex = null;
            $nextRemainder = -INF;

            foreach ($remainders as $index => $remainder) {
                if ($remainder > $nextRemainder) {
                    $nextIndex = $index;
                    $nextRemainder = $remainder;
                }
            }

            if ($nextIndex === null) {
                break;
            }

            $allocatedUnits[$nextIndex]++;
            $remainders[$nextIndex] = -INF;
            $remainingUnits--;
        }

        return array_map(
            fn(int $units): float => round($units / $scale, $precision),
            $allocatedUnits,
        );
    }

    public static function calculateItemAllocatedOrderDiscount(
        array $item,
        array $items,
        float|int|string|null $orderDiscount,
    ): float {
        $breakdown = self::calculateOrderBreakdown(
            $items,
            $orderDiscount,
            null,
            null,
        );
        $line = self::resolveBreakdownLine($item, $breakdown['lines']);

        return (float) ($line['allocated_order_discount'] ?? 0.0);
    }

    public static function calculateItemTaxAmount(
        array $item,
        array $items,
        float|int|string|null $orderDiscount,
        PurchaseOrderTaxType|string|null $taxType,
        float|int|string|null $taxPercentage,
    ): float {
        $breakdown = self::calculateOrderBreakdown(
            $items,
            $orderDiscount,
            $taxType,
            $taxPercentage,
        );
        $line = self::resolveBreakdownLine($item, $breakdown['lines']);

        return (float) ($line['tax_amount'] ?? 0.0);
    }

    public static function calculateItemFinalPrice(
        array $item,
        array $items,
        float|int|string|null $orderDiscount,
        PurchaseOrderTaxType|string|null $taxType,
        float|int|string|null $taxPercentage,
    ): float {
        $breakdown = self::calculateOrderBreakdown(
            $items,
            $orderDiscount,
            $taxType,
            $taxPercentage,
        );
        $line = self::resolveBreakdownLine($item, $breakdown['lines']);

        return (float) ($line['total'] ?? 0.0);
    }

    public static function calculateGrandTotalFromNetSubtotal(
        float|int|string|null $netSubtotal,
        PurchaseOrderTaxType|string|null $taxType,
        float|int|string|null $taxPercentage,
        float|int|string|null $rounding,
    ): float {
        $baseTotal = self::normalizeTaxType($taxType) === PurchaseOrderTaxType::EXCLUDE
            ? max((float) $netSubtotal, 0.0) + self::calculateTaxAmount($netSubtotal, $taxType, $taxPercentage)
            : max((float) $netSubtotal, 0.0);

        return max($baseTotal + (float) $rounding, 0.0);
    }

    public static function calculateGrandTotal(
        array $items,
        float|int|string|null $discount,
        PurchaseOrderTaxType|string|null $taxType,
        float|int|string|null $taxPercentage,
        float|int|string|null $rounding,
    ): float {
        return self::calculateOrderBreakdown(
            $items,
            $discount,
            $taxType,
            $taxPercentage,
            $rounding,
        )['summary']['grand_total'];
    }

    public static function syncTaxTotals(array &$data): void
    {
        $data['tax'] = self::calculateOrderBreakdown(
            $data['purchaseOrderItems'] ?? [],
            $data['discount'] ?? 0,
            $data['tax_type'] ?? null,
            $data['tax_percentage'] ?? null,
            $data['rounding'] ?? 0,
        )['summary']['tax_amount'];
    }

    public function syncCalculatedTotals(): void
    {
        $items = $this->purchaseOrderItems()
            ->get()
            ->map(fn(PurchaseOrderItem $item): array => [
                'id' => $item->id,
                'purchase_request_item_id' => $item->purchase_request_item_id,
                'qty' => (float) $item->qty,
                'price' => (float) $item->price,
                'discount' => (float) $item->discount,
            ])
            ->all();

        $taxAmount = self::calculateOrderBreakdown(
            $items,
            $this->discount,
            $this->tax_type,
            $this->tax_percentage,
            $this->rounding,
        )['summary']['tax_amount'];

        $this->forceFill([
            'tax' => $taxAmount,
        ])->saveQuietly();
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    protected static function resolveBreakdownLine(array $item, array $lines): array
    {
        $lineKey = $item['line_key'] ?? null;

        if ($lineKey !== null && array_key_exists($lineKey, $lines)) {
            return $lines[$lineKey];
        }

        $itemId = $item['id'] ?? null;

        if ($itemId !== null && array_key_exists($itemId, $lines)) {
            return $lines[$itemId];
        }

        $purchaseRequestItemId = $item['purchase_request_item_id'] ?? null;

        foreach ($lines as $line) {
            if (($line['key'] ?? null) === $purchaseRequestItemId) {
                return $line;
            }
        }

        return [];
    }

    public static function normalizeTaxType(PurchaseOrderTaxType|string|null $taxType): ?PurchaseOrderTaxType
    {
        if ($taxType instanceof PurchaseOrderTaxType) {
            return $taxType;
        }

        if (blank($taxType)) {
            return null;
        }

        return PurchaseOrderTaxType::tryFrom((string) $taxType);
    }


    protected function getWatchedFields(): array
    {
        return [
            'vendor_id',
            // 'warehouse_address_id',
            'description',
            'memo',
            'termin',
            'discount',
            'tax_type',
            'tax_percentage',
            'tax',
            'tax_description',
            'rounding',
        ];
    }

    protected function getRevisionItemsRelation(): ?string
    {
        return 'purchaseOrderItems';
    }

    protected function mapRevisionItem($item): array
    {
        return [
            'purchase_request_item_id' => (int) $item->purchase_request_item_id,
            'item_id' => (int) $item->item_id,
            'qty' => (float) $item->qty,
            'price' => (float) $item->price,
            'discount' => (float) $item->discount,
            'description' => trim((string) $item->description),
        ];
    }

    protected function mapRevisionItemFromArray(array $item): array
    {
        return [
            'purchase_request_item_id' => (int) ($item['purchase_request_item_id'] ?? 0),
            'item_id' => (int) ($item['item_id'] ?? 0),
            'qty' => (float) ($item['qty'] ?? 0),
            'price' => (float) ($item['price'] ?? 0),
            'discount' => (float) ($item['discount'] ?? 0),
            'description' => trim((string) ($item['description'] ?? '')),
        ];
    }
}
