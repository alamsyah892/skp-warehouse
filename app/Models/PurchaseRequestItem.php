<?php

namespace App\Models;

use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /* ================= RELATION ================= */

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function scopeForUserWarehouses($query, $user)
    {
        $warehouseIds = $user->warehouses()->pluck('warehouses.id');

        if ($warehouseIds->isEmpty()) {
            return $query; // tampilkan semua
        }

        return $query->whereHas('purchaseRequest', function ($q) use ($warehouseIds) {
            $q->whereIn('warehouse_id', $warehouseIds);
        });
    }
}
