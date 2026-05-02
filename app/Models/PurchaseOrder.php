<?php

namespace App\Models;

use App\Enums\GoodsReceiveStatus;
use App\Enums\PurchaseOrderType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderTaxType;
use App\Enums\PurchaseRequestStatus;
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

/**
 * @property PurchaseOrderStatus $status
 */

class PurchaseOrder extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseOrderFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString, HasDocumentNumber, HasDocumentRevision;
    use HasStateMachine {
        changeStatus as protected changeStateStatus;
    }


    /** 
     * Properties & Casts 
     */
    protected $fillable = [
        'number',
        'type',
        'description',
        'status',

        'vendor_id',
        'company_id',
        'warehouse_id',
        'division_id',
        'project_id',
        'warehouse_address_id',
        'user_id',

        'delivery_date',
        'delivery_notes',
        'shipping_method',

        'notes',
        'terms',
        'info',

        'discount',
        'tax_type',
        'tax_percentage',
        'tax_description',
        'rounding',
    ];

    protected array $defaultEmptyStringFields = [
        'number',
        'description',

        'delivery_notes',
        'shipping_method',

        'notes',
        'terms',
        'info',

        'tax_type',
        'tax_description',
    ];

    protected $casts = [
        'type' => PurchaseOrderType::class,
        'status' => PurchaseOrderStatus::class,

        'delivery_date' => 'datetime:Y-m-d',

        'discount' => 'decimal:2',
        'tax_type' => PurchaseOrderTaxType::class,
        'rounding' => 'decimal:2',
    ];


    /**
     * Constants
     */
    public const MODEL_ALIAS = 'PO';
    public const DEFAULT_TAX_PERCENTAGE = 11;
    public const SELECTABLE_PURCHASE_REQUEST_STATUSES = [
        PurchaseRequestStatus::APPROVED,
        PurchaseRequestStatus::ORDERED,
    ];


    /**
     * Booted / Events
     */
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
            $record->type ??= PurchaseOrderType::RED;

            $record->loadMissing([
                'division',
                'project',
            ]);
            $record->number = self::generateNumber($record);

            $record->status = PurchaseOrderStatus::DRAFT;
        });

        static::created(function (self $record) {
            $record->setStatusLog(PurchaseOrderStatus::DRAFT, note: (string) $record->number);
        });
    }


    /**
     * Relationships
     */
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

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function warehouseAddress(): BelongsTo
    {
        return $this->belongsTo(WarehouseAddress::class);
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

    public function goodsReceives(): HasMany
    {
        return $this->hasMany(GoodsReceive::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PurchaseOrderStatusLog::class);
    }


    /**
     * Public Helpers
     */
    public function hasStatus(PurchaseOrderStatus $status): bool
    {
        return $this->status === $status;
    }

    public function getTotalOrderedQty(): float
    {
        return (float) ($this->getAttribute('purchase_order_items_sum_qty') ?? 0);
    }

    public function getTotalReceivedQty(): float
    {
        return (float) ($this->getAttribute('purchase_order_items_received_qty_sum') ?? 0);
    }

    public function getReceivedPercentage(): float
    {
        $receivedPercentage = $this->getAttribute('purchase_order_items_received_percentage');

        if ($receivedPercentage !== null) {
            return (float) $receivedPercentage;
        }

        $totalOrderedQty = $this->getTotalOrderedQty();

        if ($totalOrderedQty <= 0) {
            return 0.0;
        }

        return round(($this->getTotalReceivedQty() / $totalOrderedQty) * 100, 2);
    }

    public function changeStatus($newStatus, ?string $note = ''): void
    {
        $normalizedStatus = $newStatus instanceof PurchaseOrderStatus
            ? $newStatus
            : PurchaseOrderStatus::from($newStatus);

        // $previousStatus = $this->status;

        $this->changeStateStatus($normalizedStatus, $note);

        if (
            $normalizedStatus === PurchaseOrderStatus::ORDERED
            // && $previousStatus !== PurchaseOrderStatus::ORDERED
        ) {
            $this->syncPurchaseRequestsToOrdered();
        }
    }

    public function scopeWithQuantitySummary(Builder $query): Builder
    {
        return $query
            ->withSum('purchaseOrderItems', 'qty')
            ->selectSub(
                GoodsReceiveItem::query()
                    ->selectRaw('coalesce(sum(goods_receive_items.qty), 0)')
                    ->join('goods_receives', 'goods_receives.id', '=', 'goods_receive_items.goods_receive_id')
                    ->join('purchase_order_items', 'purchase_order_items.id', '=', 'goods_receive_items.purchase_order_item_id')
                    ->whereColumn('purchase_order_items.purchase_order_id', 'purchase_orders.id')
                    ->whereIn('goods_receives.status', [
                        GoodsReceiveStatus::RECEIVED->value,
                        GoodsReceiveStatus::CONFIRMED->value,
                    ]),
                'purchase_order_items_received_qty_sum',
            )
            ->selectSub(
                PurchaseOrderItem::query()
                    ->selectRaw(
                        'case
                            when coalesce(sum(purchase_order_items.qty), 0) <= 0 then 0
                            else round(
                                (
                                    coalesce((
                                        select sum(goods_receive_items.qty)
                                        from goods_receive_items
                                        inner join goods_receives
                                            on goods_receives.id = goods_receive_items.goods_receive_id
                                        inner join purchase_order_items as received_purchase_order_items
                                            on received_purchase_order_items.id = goods_receive_items.purchase_order_item_id
                                        where received_purchase_order_items.purchase_order_id = purchase_orders.id
                                            and goods_receives.status in (?, ?)
                                    ), 0) / coalesce(sum(purchase_order_items.qty), 0)
                                ) * 100,
                                2
                            )
                        end',
                        [
                            GoodsReceiveStatus::RECEIVED->value,
                            GoodsReceiveStatus::CONFIRMED->value,
                        ]
                    )
                    ->whereColumn('purchase_order_items.purchase_order_id', 'purchase_orders.id'),
                'purchase_order_items_received_percentage',
            );
    }


    /**
     * for HasStateMachine
     */
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

    protected function syncPurchaseRequestsToOrdered(): void
    {
        $this->loadMissing('purchaseRequests');

        $this->purchaseRequests
            // ->filter(fn(PurchaseRequest $purchaseRequest): bool => $purchaseRequest->status === PurchaseRequestStatus::APPROVED)
            ->each(function (PurchaseRequest $purchaseRequest): void {
                $purchaseRequest->update([
                    'status' => PurchaseRequestStatus::ORDERED,
                ]);

                $purchaseRequest->setStatusLog(
                    PurchaseRequestStatus::ORDERED,
                    PurchaseRequestStatus::APPROVED,
                    (string) $this->number,
                );
            });
    }


    /**
     * for HasDocumentRevision
     */
    protected function getWatchedFields(): array
    {
        return [
            'description',

            'vendor_id',
            'warehouse_address_id',

            'delivery_date',
            'shipping_method',
            'delivery_notes',

            'terms',

            'tax_type',
            'tax_percentage',
            'tax_description',
            'discount',
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
            'description' => trim((string) $item->description),
            'sort' => (int) $item->sort,
        ];
    }

    protected function mapRevisionItemFromArray(array $item): array
    {
        return [
            'purchase_request_item_id' => (int) ($item['purchase_request_item_id'] ?? 0),
            'item_id' => (int) ($item['item_id'] ?? 0),
            'qty' => (float) ($item['qty'] ?? 0),
            'price' => (float) ($item['price'] ?? 0),
            'description' => trim((string) ($item['description'] ?? '')),
            'sort' => (int) ($item['sort'] ?? 0),
        ];
    }






    public static function calculateSubtotal(array $items): float
    {
        $normalizedItems = self::normalizeSummaryItems($items);

        return round(
            collect($normalizedItems)->sum(
                fn(array $item): float =>
                max((float) ($item['qty'] ?? 0) * (float) ($item['price'] ?? 0), 0.0)
            ),
            2
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{qty: float, price: float}>
     */
    public static function normalizeSummaryItems(array $items): array
    {
        return collect($items)
            ->filter(fn(mixed $item): bool => is_array($item))
            ->map(fn(array $item): array => [
                'qty' => max((float) ($item['qty'] ?? 0), 0.0),
                'price' => max((float) ($item['price'] ?? 0), 0.0),
            ])
            ->values()
            ->all();
    }

    public static function getTaxPercentageOptions(): array
    {
        return collect([0, 10, 11, 12])
            ->mapWithKeys(fn(int $percentage): array => [
                (int) $percentage => "{$percentage}%",
            ])
            ->all()
        ;
    }

    public static function calculateTaxAmount(
        float|int|string|null $netSubtotal,
        PurchaseOrderTaxType|string|null $taxType,
        float|int|string|null $taxPercentage,
    ): float {
        $taxableAmount = max(round((float) $netSubtotal, 2), 0.0);
        // $rate = self::resolveIndonesianVatRate($taxPercentage);
        $rate = $taxPercentage;
        $normalizedTaxType = self::normalizeTaxType($taxType);

        if ($taxableAmount <= 0 || $rate <= 0 || !$normalizedTaxType) {
            return 0.0;
        }

        return match ($normalizedTaxType) {
            PurchaseOrderTaxType::EXCLUDE => round($taxableAmount * ($rate / 100), 0),
            PurchaseOrderTaxType::INCLUDE => round($taxableAmount - ($taxableAmount / (1 + ($rate / 100))), 0),
        };
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


    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{
     *     subtotal: float,
     *     discount: float,
     *     subtotal_after_discount: float,
     *     tax_base: float,
     *     tax_amount: float,
     *     total_before_rounding: float,
     *     rounding: float,
     *     grand_total: float
     * }
     */
    public static function calculateOrderSummary(
        array $items,
        float|int|string|null $orderDiscount = 0,
        PurchaseOrderTaxType|string|null $taxType = null,
        float|int|string|null $taxPercentage = null,
        float|int|string|null $rounding = 0,
    ): array {
        $subtotal = self::calculateSubtotal($items);
        $discount = min(max(round((float) $orderDiscount, 2), 0.0), $subtotal);
        $subtotalAfterDiscount = max(round($subtotal - $discount, 2), 0.0);
        $normalizedTaxType = self::normalizeTaxType($taxType);
        $taxAmount = self::calculateTaxAmount($subtotalAfterDiscount, $normalizedTaxType, $taxPercentage);
        $tax_base = $normalizedTaxType === PurchaseOrderTaxType::INCLUDE
            ? max(round($subtotalAfterDiscount - $taxAmount, 2), 0.0)
            : $subtotalAfterDiscount;
        $totalBeforeRounding = $normalizedTaxType === PurchaseOrderTaxType::EXCLUDE
            ? round($tax_base + $taxAmount, 2)
            : $subtotalAfterDiscount;
        $rounding = round((float) $rounding, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'subtotal_after_discount' => $subtotalAfterDiscount,
            'tax_base' => $tax_base,
            'tax_amount' => $taxAmount,
            'total_before_rounding' => $totalBeforeRounding,
            'rounding' => $rounding,
            'grand_total' => max(round($totalBeforeRounding + $rounding, 2), 0.0),
        ];
    }
}
