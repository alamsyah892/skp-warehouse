<?php

namespace App\Models;

use App\Enums\PurchaseRequestStatus;
use App\Models\Concerns\DefaultEmptyString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestStatusLog extends Model
{
    use DefaultEmptyString;

    protected $fillable = [
        'purchase_request_id',
        'user_id',
        'from_status',
        'to_status',
        'note',
    ];

    protected array $defaultEmptyStringFields = [
        'note',
    ];

    protected $casts = [
        'from_status' => PurchaseRequestStatus::class,
        'to_status' => PurchaseRequestStatus::class,
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
