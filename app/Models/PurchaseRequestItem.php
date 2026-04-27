<?php

namespace App\Models;

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

    public function getOrderedQtyColor(): string
    {
        $orderedQty = $this->getOrderedQty();
        $requestedQty = (float) $this->qty;

        return match (true) {
            $orderedQty == 0.0 => 'danger',
            $orderedQty < $requestedQty => 'warning',
            default => 'success',
        };
    }

    public function getRemainingQty(?int $exceptPurchaseOrderId = null): float
    {
        $remaining = (float) $this->qty - $this->getOrderedQty($exceptPurchaseOrderId);

        return max($remaining, 0.0);
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
