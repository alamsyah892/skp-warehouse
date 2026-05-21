<?php

namespace App\Models;

use App\Enums\GoodsReceiveStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class PurchaseRequestItem extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseRequestItemFactory> */
    use HasFactory;

    use LogsAllFillable, DefaultEmptyString;


    protected $fillable = [
        'purchase_request_id',
        'item_id',

        'qty',
        'description',
        'sort',
    ];

    protected array $defaultEmptyStringFields = [
        'description',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];


    /**
     * Relationships
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }


    public function getOrderedQty(?int $exceptPurchaseOrderId = null): float
    {
        return (float) $this->purchaseOrderItems()
            ->whereHas('purchaseOrder', function ($query) use ($exceptPurchaseOrderId) {
                $query->where('status', '!=', PurchaseOrderStatus::CANCELED);

                if ($exceptPurchaseOrderId) {
                    $query->where('id', '!=', $exceptPurchaseOrderId);
                }
            })
            ->sum('qty')
        ;
    }

    public function getTotalOrderedQty(): float
    {
        $orderedQty = $this->getAttribute('purchase_order_items_ordered_qty_sum');

        if ($orderedQty !== null) {
            return (float) $orderedQty;
        }

        return $this->getOrderedQty();
    }

    public function getOrderedQtyColor(): string
    {
        $orderedQty = $this->getOrderedQty();
        $requestedQty = (float) $this->qty;

        return match (true) {
            $orderedQty == 0 => 'gray',
            $orderedQty < $requestedQty => 'warning',
            default => 'success',
        };
    }

    public function getRemainingQty(?int $exceptPurchaseOrderId = null): float
    {
        $remaining = (float) $this->qty - $this->getOrderedQty($exceptPurchaseOrderId);

        return max($remaining, 0.0);
    }

    public function getRemainingOrderedQty(): float
    {
        return max((float) $this->qty - $this->getTotalOrderedQty(), 0.0);
    }

    public function getOrderedPercentage(): float
    {
        $orderedPercentage = $this->getAttribute('purchase_order_items_ordered_percentage');

        if ($orderedPercentage !== null) {
            return (float) $orderedPercentage;
        }

        $requestedQty = (float) $this->qty;

        if ($requestedQty <= 0) {
            return 0.0;
        }

        return round(($this->getTotalOrderedQty() / $requestedQty) * 100, 2);
    }

    public function getTotalReceivedQty(): float
    {
        $receivedQty = $this->getAttribute('purchase_order_items_received_qty_sum');

        if ($receivedQty !== null) {
            return (float) $receivedQty;
        }

        return (float) GoodsReceiveItem::query()
            ->join('goods_receives', 'goods_receives.id', '=', 'goods_receive_items.goods_receive_id')
            ->join('purchase_order_items', 'purchase_order_items.id', '=', 'goods_receive_items.purchase_order_item_id')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->where('purchase_order_items.purchase_request_item_id', $this->getKey())
            ->where('purchase_orders.status', '!=', PurchaseOrderStatus::CANCELED->value)
            ->whereIn('goods_receives.status', [
                GoodsReceiveStatus::RECEIVED->value,
                GoodsReceiveStatus::CONFIRMED->value,
            ])
            ->sum('goods_receive_items.qty');
    }

    public function getRemainingReceivedQty(): float
    {
        return max((float) $this->qty - $this->getTotalReceivedQty(), 0.0);
    }

    public function getReceivedPercentage(): float
    {
        $receivedPercentage = $this->getAttribute('purchase_order_items_received_percentage');

        if ($receivedPercentage !== null) {
            return (float) $receivedPercentage;
        }

        $requestedQty = (float) $this->qty;

        if ($requestedQty <= 0) {
            return 0.0;
        }

        return round(($this->getTotalReceivedQty() / $requestedQty) * 100, 2);
    }

    public function scopeWithQuantitySummary(Builder $query): Builder
    {
        return $query
            ->addSelect('purchase_request_items.*')
            ->selectSub(
                PurchaseOrderItem::query()
                    ->selectRaw('coalesce(sum(purchase_order_items.qty), 0)')
                    ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
                    ->whereColumn('purchase_order_items.purchase_request_item_id', 'purchase_request_items.id')
                    ->where('purchase_orders.status', '!=', PurchaseOrderStatus::CANCELED->value),
                'purchase_order_items_ordered_qty_sum',
            )
            ->selectSub(
                GoodsReceiveItem::query()
                    ->selectRaw('coalesce(sum(goods_receive_items.qty), 0)')
                    ->join('goods_receives', 'goods_receives.id', '=', 'goods_receive_items.goods_receive_id')
                    ->join('purchase_order_items', 'purchase_order_items.id', '=', 'goods_receive_items.purchase_order_item_id')
                    ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
                    ->whereColumn('purchase_order_items.purchase_request_item_id', 'purchase_request_items.id')
                    ->where('purchase_orders.status', '!=', PurchaseOrderStatus::CANCELED->value)
                    ->whereIn('goods_receives.status', [
                        GoodsReceiveStatus::RECEIVED->value,
                        GoodsReceiveStatus::CONFIRMED->value,
                    ]),
                'purchase_order_items_received_qty_sum',
            )
            ->selectRaw(
                'case
                    when coalesce(purchase_request_items.qty, 0) <= 0 then 0
                    else round(
                        (
                            coalesce((
                                select sum(ordered_purchase_order_items.qty)
                                from purchase_order_items as ordered_purchase_order_items
                                inner join purchase_orders
                                    on purchase_orders.id = ordered_purchase_order_items.purchase_order_id
                                where ordered_purchase_order_items.purchase_request_item_id = purchase_request_items.id
                                    and purchase_orders.status != ?
                            ), 0) / coalesce(purchase_request_items.qty, 0)
                        ) * 100,
                        2
                    )
                end as purchase_order_items_ordered_percentage',
                [PurchaseOrderStatus::CANCELED->value]
            )
            ->selectRaw(
                'case
                    when coalesce(purchase_request_items.qty, 0) <= 0 then 0
                    else round(
                        (
                            coalesce((
                                select sum(received_goods_receive_items.qty)
                                from goods_receive_items as received_goods_receive_items
                                inner join goods_receives
                                    on goods_receives.id = received_goods_receive_items.goods_receive_id
                                inner join purchase_order_items
                                    on purchase_order_items.id = received_goods_receive_items.purchase_order_item_id
                                inner join purchase_orders
                                    on purchase_orders.id = purchase_order_items.purchase_order_id
                                where purchase_order_items.purchase_request_item_id = purchase_request_items.id
                                    and purchase_orders.status != ?
                                    and goods_receives.status in (?, ?)
                            ), 0) / coalesce(purchase_request_items.qty, 0)
                        ) * 100,
                        2
                    )
                end as purchase_order_items_received_percentage',
                [
                    PurchaseOrderStatus::CANCELED->value,
                    GoodsReceiveStatus::RECEIVED->value,
                    GoodsReceiveStatus::CONFIRMED->value,
                ]
            );
    }


    public static function getOptions(array $purchaseRequestIds, ?string $search = null): array
    {
        $purchaseRequestIds = PurchaseRequest::normalizeIds($purchaseRequestIds);

        if (blank($purchaseRequestIds)) {
            return [];
        }

        static $cache = [];

        $cacheKey = md5(json_encode([
            'purchase_request_ids' => $purchaseRequestIds,
            'search' => filled($search) ? trim($search) : null,
        ], JSON_THROW_ON_ERROR));

        if (!array_key_exists($cacheKey, $cache)) {
            $query = self::getCompatibleForPurchaseOrderQuery($purchaseRequestIds);

            if (filled($search)) {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder->whereHas('item', function (Builder $itemQuery) use ($search): void {
                        $itemQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })->orWhereHas('purchaseRequest', function (Builder $purchaseRequestQuery) use ($search): void {
                        $purchaseRequestQuery->where('number', 'like', "%{$search}%");
                    });
                });
            }

            $cache[$cacheKey] = $query
                ->limit(50)
                ->get()
                ->groupBy(fn(self $record) => $record->purchaseRequest?->number ?? '-')
                ->map(function (Collection $items): Collection {
                    return $items->mapWithKeys(fn(self $record): array => [
                        $record->id => "{$record->purchaseRequest?->number} | {$record->item?->code} | {$record->item?->name}",
                    ]);
                })
                ->toArray();
        }

        return $cache[$cacheKey];
    }

    public static function findWithDetail(?int $id): ?self
    {
        if (!$id) {
            return null;
        }

        static $cache = [];

        if (!array_key_exists($id, $cache)) {
            $cache[$id] = self::query()
                ->with(['item', 'purchaseRequest'])
                ->find($id);
        }

        return $cache[$id];
    }

    public static function getCompatibleForPurchaseOrderQuery(array $purchaseRequestIds = []): Builder
    {
        return self::query()
            ->with([
                'item:id,code,name,unit',
                'purchaseRequest:id,number,warehouse_id,company_id,division_id,project_id,status',
            ])
            ->whereHas('purchaseRequest', function (Builder $query) use ($purchaseRequestIds): void {
                if (blank($purchaseRequestIds)) {
                    return;
                }

                $query->whereIn('id', $purchaseRequestIds);
            });
    }
}
