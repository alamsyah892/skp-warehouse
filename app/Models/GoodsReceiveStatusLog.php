<?php

namespace App\Models;

use App\Enums\GoodsReceiveStatus;
use App\Models\Concerns\DefaultEmptyString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiveStatusLog extends Model
{
    use DefaultEmptyString;

    /**
     * Properties & Casts
     */
    protected $fillable = [
        'goods_receive_id',
        'user_id',
        'from_status',
        'to_status',
        'note',
    ];

    protected array $defaultEmptyStringFields = [
        'note',
    ];

    protected $casts = [
        'from_status' => GoodsReceiveStatus::class,
        'to_status' => GoodsReceiveStatus::class,
    ];

    /**
     * Relationships
     */
    public function goodsReceive(): BelongsTo
    {
        return $this->belongsTo(GoodsReceive::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
