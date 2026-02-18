<?php

namespace App\Models;

use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseRequestFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString;

    public const TYPE_PURCHASE = 1;
    public const TYPE_LOAN = 2;

    public const TYPE_LABELS = [
        self::TYPE_PURCHASE => 'Pengajuan Pembelian',
        self::TYPE_LOAN => 'Pengajuan Peminjaman',
    ];

    public const STATUS_DRAFT = 1;
    public const STATUS_CANCELED = 2;
    public const STATUS_WAITING = 3;
    public const STATUS_RECEIVED = 4;
    public const STATUS_ORDERED = 5;
    public const STATUS_FINISH = 6;

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_CANCELED => 'Canceled',
        self::STATUS_WAITING => 'Waiting',
        self::STATUS_RECEIVED => 'Received',
        self::STATUS_ORDERED => 'Ordered',
        self::STATUS_FINISH => 'Finish',
    ];

    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'gray',
        self::STATUS_CANCELED => 'danger',
        self::STATUS_WAITING => 'warning',
        self::STATUS_RECEIVED => 'primary',
        self::STATUS_ORDERED => 'info',
        self::STATUS_FINISH => 'success',
    ];

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'warehouse_address_id',
        'division_id',
        'project_id',
        'user_id',

        'type',

        'number',
        'description',
        'memo',
        'boq',
        'notes',

        'info',

        'status',
    ];

    protected array $defaultEmptyStringFields = [
        'description',
        'memo',
        'boq',
        'notes',

        'info',
    ];

    /* ================= RELATION ================= */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseAddress(): BelongsTo
    {
        return $this->belongsTo(WarehouseAddress::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseRequestItems(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }
}
