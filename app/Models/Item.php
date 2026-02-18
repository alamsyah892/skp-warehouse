<?php

namespace App\Models;

use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    /** @use HasFactory<\Database\Factories\ItemFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString;

    public const TYPE_STOCKABLE = 1;
    public const TYPE_CONSUMABLE = 2;

    public const TYPE_LABELS = [
        self::TYPE_STOCKABLE => 'Stock Item',
        self::TYPE_CONSUMABLE => 'Consumable / Once Use',
    ];

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_INACTIVE => 'Inactive',
    ];

    protected $fillable = [
        'category_id',

        'code',
        'name',
        'description',

        'unit',
        'type',

        'is_active',
    ];

    protected array $defaultEmptyStringFields = [
        'description',
    ];

    /* ================= RELATION ================= */

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class);
    }

    public function purchaseRequestItems(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }
}
