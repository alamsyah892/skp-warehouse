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
