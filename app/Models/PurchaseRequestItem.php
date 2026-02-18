<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestItem extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseRequestItemFactory> */
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'item_id',

        'qty',
        'description',
    ];

    protected array $defaultEmptyStringFields = [
        'description',
    ];

    /* ================= RELATION ================= */

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
