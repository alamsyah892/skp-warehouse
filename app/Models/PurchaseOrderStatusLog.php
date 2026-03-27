<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderStatusLog extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'user_id',
        'from_status',
        'to_status',
        'note',
    ];

    protected $casts = [
        'from_status' => PurchaseOrderStatus::class,
        'to_status' => PurchaseOrderStatus::class,
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
