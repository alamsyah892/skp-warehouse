<?php

namespace App\Models;

use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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


    public function getLineTotalAmount(): float
    {
        return PurchaseOrder::calculateItemTotal([
            'qty' => $this->qty,
            'price' => $this->price,
        ]);
    }
}
