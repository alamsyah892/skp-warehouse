<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
                // $query->where('status', '=', PurchaseOrderStatus::ORDERED);
    
                if ($exceptPurchaseOrderId) {
                    $query->where('id', '!=', $exceptPurchaseOrderId);
                }
            })
            ->sum('qty');
    }

    public function getRemainingQty(?int $exceptPurchaseOrderId = null): float
    {
        $remaining = (float) $this->qty - $this->getOrderedQty($exceptPurchaseOrderId);

        return max($remaining, 0.0);
    }

    // public function scopeForUserWarehouses($query, $user)
    // {
    //     $warehouseIds = $user->warehouses()->pluck('warehouses.id');

    //     if ($warehouseIds->isEmpty()) {
    //         return $query; // tampilkan semua
    //     }

    //     return $query->whereHas('purchaseRequest', function ($q) use ($warehouseIds) {
    //         $q->whereIn('warehouse_id', $warehouseIds);
    //     });
    // }
}
