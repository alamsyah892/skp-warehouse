<?php

namespace App\Models;

use App\Enums\GoodsReceiveStatus;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class PurchaseOrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseOrderItemFactory> */
    use HasFactory;

    use LogsAllFillable, DefaultEmptyString;


    /** 
     * Properties & Casts 
     */
    protected $fillable = [
        'purchase_order_id',
        'item_id',

        'purchase_request_item_id',

        'qty',
        'price',
        'description',
        'sort',
    ];

    protected array $defaultEmptyStringFields = [
        'description',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'price' => 'decimal:2',
    ];


    /**
     * Relationships
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    public function goodsReceiveItems(): HasMany
    {
        return $this->hasMany(GoodsReceiveItem::class);
    }


    public function getSubtotalAmount(): float
    {
        return $this->qty * $this->price;
    }

    public function getTotalReceivedQty(): float
    {
        $receivedQty = $this->getAttribute('goods_receive_items_received_qty_sum');

        if ($receivedQty !== null) {
            return (float) $receivedQty;
        }

        return $this->getReceivedQty();
    }


    public function getReceivedQty(?int $exceptGoodsReceiveId = null): float
    {
        return (float) $this->goodsReceiveItems()
            ->whereHas('goodsReceive', function ($query) use ($exceptGoodsReceiveId) {
                $query->whereIn('status', [
                    GoodsReceiveStatus::RECEIVED,
                    GoodsReceiveStatus::CONFIRMED,
                ]);

                if ($exceptGoodsReceiveId) {
                    $query->where('id', '!=', $exceptGoodsReceiveId);
                }
            })
            ->sum('qty');
    }

    public function getReceivedQtyColor(): string
    {
        $receivedQty = $this->getReceivedQty();
        $orderedQty = (float) $this->qty;

        return match (true) {
            $receivedQty == 0 => 'gray',
            $receivedQty < $orderedQty => 'warning',
            default => 'success',
        };
    }

    public function getRemainingQty(?int $exceptGoodsReceiveId = null): float
    {
        $remaining = (float) $this->qty - $this->getReceivedQty($exceptGoodsReceiveId);

        return max($remaining, 0.0);
    }

    public function getRemainingReceivedQty(): float
    {
        return max((float) $this->qty - $this->getTotalReceivedQty(), 0.0);
    }

    public function getReceivedPercentage(): float
    {
        $receivedPercentage = $this->getAttribute('goods_receive_items_received_percentage');

        if ($receivedPercentage !== null) {
            return (float) $receivedPercentage;
        }

        $orderedQty = (float) $this->qty;

        if ($orderedQty <= 0) {
            return 0.0;
        }

        return round(($this->getTotalReceivedQty() / $orderedQty) * 100, 2);
    }

    public function scopeWithQuantitySummary(Builder $query): Builder
    {
        return $query
            ->addSelect('purchase_order_items.*')
            ->selectSub(
                GoodsReceiveItem::query()
                    ->selectRaw('coalesce(sum(goods_receive_items.qty), 0)')
                    ->join('goods_receives', 'goods_receives.id', '=', 'goods_receive_items.goods_receive_id')
                    ->whereColumn('goods_receive_items.purchase_order_item_id', 'purchase_order_items.id')
                    ->whereIn('goods_receives.status', [
                        GoodsReceiveStatus::RECEIVED->value,
                        GoodsReceiveStatus::CONFIRMED->value,
                    ]),
                'goods_receive_items_received_qty_sum',
            )
            ->selectRaw(
                'case
                    when coalesce(purchase_order_items.qty, 0) <= 0 then 0
                    else round(
                        (
                            coalesce((
                                select sum(received_goods_receive_items.qty)
                                from goods_receive_items as received_goods_receive_items
                                inner join goods_receives
                                    on goods_receives.id = received_goods_receive_items.goods_receive_id
                                where received_goods_receive_items.purchase_order_item_id = purchase_order_items.id
                                    and goods_receives.status in (?, ?)
                            ), 0) / coalesce(purchase_order_items.qty, 0)
                        ) * 100,
                        2
                    )
                end as goods_receive_items_received_percentage',
                [
                    GoodsReceiveStatus::RECEIVED->value,
                    GoodsReceiveStatus::CONFIRMED->value,
                ]
            );
    }


    public static function getOptions(int $purchaseOrderId, ?string $search = null): array
    {
        if ($purchaseOrderId <= 0) {
            return [];
        }

        static $cache = [];

        $cacheKey = md5(json_encode([
            'purchase_order_id' => $purchaseOrderId,
            'search' => filled($search) ? trim($search) : null,
        ], JSON_THROW_ON_ERROR));

        if (!array_key_exists($cacheKey, $cache)) {
            $query = PurchaseOrderItem::query()
                ->where('purchase_order_id', $purchaseOrderId)
                ->with([
                    'item:id,code,name',
                    'purchaseRequestItem.purchaseRequest:id,number',
                ])
                ->orderBy('sort');

            if (filled($search)) {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->whereHas('item', function (Builder $itemQuery) use ($search): void {
                            $itemQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('purchaseRequestItem.purchaseRequest', function (Builder $prQuery) use ($search): void {
                            $prQuery->where('number', 'like', "%{$search}%");
                        });
                });
            }

            $cache[$cacheKey] = $query
                ->limit(50)
                ->get()
                ->groupBy(fn(self $record): string => $record->purchaseRequestItem?->purchaseRequest?->number ?? '-')
                ->map(function (Collection $items): Collection {
                    return $items->mapWithKeys(function (self $record): array {
                        return [
                            $record->id => "{$record->item?->code} | {$record->item?->name}",
                        ];
                    });
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
                ->with(['item', 'purchaseOrder'])
                ->find($id);
        }

        return $cache[$id];
    }
}
