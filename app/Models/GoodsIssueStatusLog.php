<?php

namespace App\Models;

use App\Enums\GoodsIssueStatus;
use App\Models\Concerns\DefaultEmptyString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsIssueStatusLog extends Model
{
    use DefaultEmptyString;

    protected $fillable = [
        'goods_issue_id',
        'user_id',
        'from_status',
        'to_status',
        'note',
    ];

    protected array $defaultEmptyStringFields = [
        'note',
    ];

    protected $casts = [
        'from_status' => GoodsIssueStatus::class,
        'to_status' => GoodsIssueStatus::class,
    ];

    public function goodsIssue(): BelongsTo
    {
        return $this->belongsTo(GoodsIssue::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
