<?php

namespace App\Models;

use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Filament\Support\Icons\Heroicon;
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


    public const MODEL_ALIAS = 'BPPB';


    /* ================= TYPE ================= */
    public const TYPE_PURCHASE = 1;
    public const TYPE_LOAN = 2;
    public const TYPE_LABELS = [
        self::TYPE_PURCHASE => 'Pengajuan Pembelian',
        self::TYPE_LOAN => 'Pengajuan Peminjaman',
    ];


    /* ================= STATUS ================= */
    // default status when creating new purchase request
    public const STATUS_DRAFT = 1;

    // can be set from any status (except finished), and can be set back to waiting
    public const STATUS_CANCELED = 2;

    // after submit, waiting for approval. can be set to canceled, or set to approved if approved
    public const STATUS_WAITING = 3;

    // after approved, waiting for order. can be set to canceled, or set to ordered if items are ordered
    public const STATUS_APPROVED = 4;

    // after items are ordered, waiting for delivery. can be set to canceled, or set to finished if items are delivered
    public const STATUS_ORDERED = 5;

    // all items have been delivered and the request is complete. can not be changed anymore
    public const STATUS_FINISHED = 6;

    public const STATUSES = [

        self::STATUS_DRAFT => [
            'label' => 'purchase-request.status.draft',
            'color' => 'gray',
            'icon' => Heroicon::OutlinedPencilSquare,
        ],

        self::STATUS_CANCELED => [
            'label' => 'purchase-request.status.canceled',
            'color' => 'danger',
            'icon' => Heroicon::OutlinedXCircle,
        ],

        self::STATUS_WAITING => [
            'label' => 'purchase-request.status.waiting',
            'color' => 'warning',
            'icon' => Heroicon::OutlinedClock,
        ],

        self::STATUS_APPROVED => [
            'label' => 'purchase-request.status.approved',
            'color' => 'primary',
            'icon' => Heroicon::OutlinedInboxArrowDown,
        ],

        self::STATUS_ORDERED => [
            'label' => 'purchase-request.status.ordered',
            'color' => 'info',
            'icon' => Heroicon::OutlinedShoppingCart,
        ],

        self::STATUS_FINISHED => [
            'label' => 'purchase-request.status.finished',
            'color' => 'success',
            'icon' => Heroicon::OutlinedCheckCircle,
        ],

    ];

    public const STATUS_FLOW = [

        self::STATUS_DRAFT => [
            self::STATUS_CANCELED,
            self::STATUS_WAITING,
        ],

        self::STATUS_CANCELED => [
            self::STATUS_WAITING,
        ],

        self::STATUS_WAITING => [
            self::STATUS_CANCELED,
            self::STATUS_APPROVED,
        ],

        self::STATUS_APPROVED => [
            self::STATUS_CANCELED,
            self::STATUS_ORDERED,
        ],

        self::STATUS_ORDERED => [
            self::STATUS_CANCELED,
            self::STATUS_FINISHED,
        ],

        self::STATUS_FINISHED => [],
    ];


    protected static function booted(): void
    {
        static::addGlobalScope('user_warehouses', function ($builder) {
            if (app()->runningInConsole() || !auth()->check()) {
                return;
            }

            $userWarehouseIds = auth()->user()->warehouses->pluck('id');
            if ($userWarehouseIds->isNotEmpty()) {
                $builder->whereHas('warehouse', function ($q) use ($userWarehouseIds) {
                    $q->whereIn('warehouses.id', $userWarehouseIds);
                });
            }
        });


        static::creating(function ($record) {
            $record->user_id = auth()->id();
            $record->type = self::TYPE_PURCHASE;

            $record->number = self::generateNumber($record);

            $record->status = self::STATUS_DRAFT;
        });


        static::updating(function ($record) {
            if ($record->isDirty() && $record->status !== self::STATUS_DRAFT) {
                $record->number = self::generateRevisionNumber($record->number);
            }
        });
    }


    /* ================= NUMBER GENERATORS ================= */
    protected static function generateNumber(self $record): string
    {
        $year = now()->format('y');
        $month = now()->format('m');

        $prefix = sprintf(
            '%s/%s/%s/%s%s%s%s',
            self::MODEL_ALIAS,
            $year,
            $month,
            $record->warehouse->code,
            $record->company->code,
            $record->division->code,
            $record->project->code,
        );

        $last = static::where('number', 'like', "{$prefix}/%")
            ->latest('id')
            ->value('number');

        $lastSequence = 0;

        if ($last && preg_match('/\/(\d+)$/', $last, $match)) {
            $lastSequence = (int) $match[1];
        }

        $sequence = str_pad($lastSequence + 1, 3, '0', STR_PAD_LEFT);

        return "{$prefix}/{$sequence}";
    }

    protected static function generateRevisionNumber(string $number): string
    {
        $rev = 0;

        if (preg_match('/-Rev\.(\d+)$/', $number, $match)) {
            $rev = (int) $match[1];
        }

        $rev++;

        $revNumber = str_pad($rev, 2, '0', STR_PAD_LEFT);

        $baseNumber = preg_replace('/-Rev\.\d+$/', '', $number);

        return "{$baseNumber}-Rev.{$revNumber}";
    }


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
        return $this->hasMany(PurchaseRequestItem::class)->orderBy('sort');
    }


    /* ================= STATUS HELPERS ================= */
    public static function getStatusLabels(): array
    {
        return collect(self::STATUSES)
            ->mapWithKeys(fn($meta, $status) => [
                $status => __($meta['label'])
            ])
            ->toArray();
    }

    public static function getStatusColor(int $status): string
    {
        return self::STATUSES[$status]['color'] ?? 'gray';
    }

    public static function getStatusIcon(int $status)
    {
        return self::STATUSES[$status]['icon'];
    }

    // public function getStatusLabelAttribute(): string
    // {
    //     return __(self::STATUSES[$this->status]['label'] ?? $this->status);
    // }

    /* ================= STATUS WORKFLOW ================= */
    public function getNextStatuses(): array
    {
        return self::STATUS_FLOW[$this->status] ?? [];
    }

    public function canChangeStatusTo(int $status): bool
    {
        return in_array($status, $this->getNextStatuses(), true);
    }

    /* ================= STATUS CHECKERS ================= */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isOrdered(): bool
    {
        return $this->status === self::STATUS_ORDERED;
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

}
