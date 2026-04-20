<?php

namespace App\Models;

use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiveItem extends Model
{
    /** @use HasFactory<\Database\Factories\GoodsReceiveItemFactory> */
    use HasFactory;

    use LogsAllFillable, DefaultEmptyString;

    /**
     * Properties & Casts
     */
    protected $fillable = [
        'goods_receive_id',
        'purchase_order_item_id',
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
    public function goodsReceive(): BelongsTo
    {
        return $this->belongsTo(GoodsReceive::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
