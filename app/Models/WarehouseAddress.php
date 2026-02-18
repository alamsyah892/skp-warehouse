<?php

namespace App\Models;

use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseAddress extends Model
{
    /** @use HasFactory<\Database\Factories\VendorFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString;

    protected $fillable = [
        'warehouse_id',

        'address',
        'city',
        'post_code',
        'phone',
        'fax',

        'as_default',
    ];

    protected array $defaultEmptyStringFields = [
        'post_code',
        'phone',
        'fax',
    ];

    /* ================= RELATION ================= */

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
