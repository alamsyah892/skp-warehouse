<?php

namespace App\Models;

use App\Enums\GoodsReceiveStatus;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function getReceivedQty(?int $exceptGoodsReceiveId = null): float
    {
        return (float) $this->goodsReceiveItems()
            ->whereHas('goodsReceive', function ($query) use ($exceptGoodsReceiveId) {
                $query->where('status', GoodsReceiveStatus::RECEIVED->value);

                if ($exceptGoodsReceiveId) {
                    $query->where('id', '!=', $exceptGoodsReceiveId);
                }
            })
            ->sum('qty');
    }

    public function getRemainingReceiveQty(?int $exceptGoodsReceiveId = null): float
    {
        $remaining = (float) $this->qty - $this->getReceivedQty($exceptGoodsReceiveId);

        return max($remaining, 0.0);
    }


    public function getLineTotalAmount(): float
    {
        return PurchaseOrder::calculateItemTotal([
            'qty' => $this->qty,
            'price' => $this->price,
        ]);
    }
}
