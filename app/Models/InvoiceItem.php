<?php

namespace App\Models;

use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class InvoiceItem extends Model
{
    use DefaultEmptyString, LogsAllFillable;

    /** @use HasFactory<InvoiceItemFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'goods_receive_item_id',
        'item_id',
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

    public function getSubtotalAttribute(): float
    {
        return (float) $this->qty * (float) $this->price;
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function goodsReceiveItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiveItem::class);
    }

    public function purchaseOrderItem(): HasOneThrough
    {
        return $this->hasOneThrough(
            PurchaseOrderItem::class,
            GoodsReceiveItem::class,
            'id',
            'id',
            'goods_receive_item_id',
            'purchase_order_item_id',
        );
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
