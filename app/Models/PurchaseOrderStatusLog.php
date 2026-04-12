<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\DefaultEmptyString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderStatusLog extends Model
{
    use DefaultEmptyString;

    /** 
     * Properties & Casts 
     */
    protected $fillable = [
        'purchase_order_id',
        'user_id',
        'from_status',
        'to_status',

        'note',
    ];

    protected array $defaultEmptyStringFields = [
        'note',
    ];

    protected $casts = [
        'from_status' => PurchaseOrderStatus::class,
        'to_status' => PurchaseOrderStatus::class,
    ];


    /**
     * Relationships
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
