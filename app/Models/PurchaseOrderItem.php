<?php

namespace App\Models;

use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseOrderItemFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString;

    protected $fillable = [
        'purchase_order_id',
        'purchase_request_item_id',
        'item_id',
        'qty',
        'price',
        'description',
        'sort',
    ];

    protected array $defaultEmptyStringFields = [
        'description',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
