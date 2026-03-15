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
use Illuminate\Support\Facades\DB;

class PurchaseRequest extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseRequestFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString;

    public const MODEL_ALIAS = 'BPPB';
    public const TYPE_PURCHASE_REQUEST = 1;

    public const STATUS_DRAFT = 1;
    public const STATUS_CANCELED = 2;
    public const STATUS_REQUESTED = 3;
    public const STATUS_APPROVED = 4;
    public const STATUS_ORDERED = 5;
    public const STATUS_FINISHED = 6;

    public const STATUSES = [
        self::STATUS_DRAFT => [
            'label' => 'purchase-request.status.draft',
            'action_label' => 'purchase-request.action.label.draft',
            'color' => 'gray',
            'icon' => Heroicon::OutlinedPencilSquare,
        ],
        self::STATUS_CANCELED => [
            'label' => 'purchase-request.status.canceled',
            'action_label' => 'purchase-request.action.label.canceled',
            'color' => 'danger',
            'icon' => Heroicon::OutlinedXCircle,
        ],
        self::STATUS_REQUESTED => [
            'label' => 'purchase-request.status.requested',
            'action_label' => 'purchase-request.action.label.requested',
            'color' => 'warning',
            'icon' => Heroicon::OutlinedClock,
        ],
        self::STATUS_APPROVED => [
            'label' => 'purchase-request.status.approved',
            'action_label' => 'purchase-request.action.label.approved',
            'color' => 'primary',
            'icon' => Heroicon::OutlinedInboxArrowDown,
        ],
        self::STATUS_ORDERED => [
            'label' => 'purchase-request.status.ordered',
            'action_label' => 'purchase-request.action.label.ordered',
            'color' => 'info',
            'icon' => Heroicon::OutlinedShoppingCart,
        ],
        self::STATUS_FINISHED => [
            'label' => 'purchase-request.status.finished',
            'action_label' => 'purchase-request.action.label.finished',
            'color' => 'success',
            'icon' => Heroicon::OutlinedCheckCircle,
        ],
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

    protected static function booted(): void
    {
        static::addGlobalScope('user_warehouses', function ($builder) {
            if (app()->runningInConsole() || !auth()->check()) {
                return;
            }

            $userWarehouseIds = auth()->user()->warehouses()->pluck('warehouses.id');
            if ($userWarehouseIds->isNotEmpty()) {
                $builder->whereHas('warehouse', function ($q) use ($userWarehouseIds) {
                    $q->whereIn('warehouses.id', $userWarehouseIds);
                });
            }
        });

        static::creating(function ($record) {
            $record->user_id ??= auth()->id();
            $record->type = self::TYPE_PURCHASE_REQUEST;
            $record->number = self::generateNumber($record);
            $record->status = self::STATUS_DRAFT;
        });

        static::updating(function ($record) {
            $watchedFields = [
                'description',
                // 'qty',
                // 'price',
                // 'supplier_id',
            ];

            $dirty = $record->getDirty();

            $changedWatchedField = false;

            foreach ($watchedFields as $field) {
                if (array_key_exists($field, $dirty)) {
                    $changedWatchedField = true;
                    break;
                }
            }

            if (!$changedWatchedField) {
                return;
            }

            if ($record->status !== self::STATUS_DRAFT) {
                $record->number = $record->incrementRevision();
            }
        });
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
    protected static ?array $statusLabels = null;

    public static function getStatusLabels(): array
    {
        if (static::$statusLabels === null) {
            static::$statusLabels = collect(self::STATUSES)
                ->mapWithKeys(fn($meta, $status) => [$status => __($meta['label'])])
                ->toArray();
        }

        return static::$statusLabels;
    }

    public static function getStatusLabel(int $status): string
    {
        return __(self::STATUSES[$status]['label']);
    }

    public static function getStatusActionLabel(int $status): string
    {
        return __(self::STATUSES[$status]['action_label']);
    }

    public static function getStatusColor(int $status): string
    {
        return self::STATUSES[$status]['color'] ?? 'gray';
    }

    public static function getStatusIcon(int $status)
    {
        return self::STATUSES[$status]['icon'];
    }

    /* ================= STATUS WORKFLOW ================= */
    public const STATUS_FLOW = [
        self::STATUS_DRAFT => [
            self::STATUS_CANCELED,
            self::STATUS_REQUESTED,
        ], // default status when creating new purchase request, can be set to requested or canceled
        self::STATUS_CANCELED => [
            self::STATUS_REQUESTED,
        ], // can be set from any status (except finished), and can be set back to requested
        self::STATUS_REQUESTED => [
            self::STATUS_CANCELED,
            self::STATUS_APPROVED,
        ], // requested for approval. can be set to canceled, or set to approved if approved
        self::STATUS_APPROVED => [
            self::STATUS_CANCELED,
        ], // after approved, can be set to canceled, or set to ordered if items are ordered
        self::STATUS_ORDERED => [
            self::STATUS_CANCELED,
            self::STATUS_FINISHED,
        ], // after items are ordered, requested for delivery. can be set to canceled, or set to finished if items are delivered
        self::STATUS_FINISHED => [],// all items have been delivered and the request is complete. can not be changed anymore
    ];

    public function getNextStatuses(): array
    {
        return self::STATUS_FLOW[$this->status] ?? [];
    }

    public function canChangeStatusTo(int $status): bool
    {
        return in_array($status, $this->getNextStatuses(), true);
    }

    public function changeStatus(int $newStatus): void
    {
        if (!$this->canChangeStatusTo($newStatus)) {
            throw new \Exception("Invalid status transition");
        }

        $this->update([
            'status' => $newStatus,
        ]);
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
        return $this->status === self::STATUS_REQUESTED;
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

    /* ================= NUMBER GENERATORS ================= */
    protected static function generateNumber(self $record): string
    {
        return DB::transaction(function () use ($record) {

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
                ->lockForUpdate()
                ->orderByDesc('number')
                ->value('number');

            $lastSequence = 0;

            if ($last && preg_match('/\/(\d+)$/', $last, $match)) {
                $lastSequence = (int) $match[1];
            }

            $sequence = str_pad($lastSequence + 1, 3, '0', STR_PAD_LEFT);

            return "{$prefix}/{$sequence}";
        });
    }

    public function incrementRevision(): string
    {
        $rev = $this->getCurrentRevision() + 1;

        $base = preg_replace('/-Rev\.\d+$/', '', $this->number);

        return sprintf('%s-Rev.%02d', $base, $rev);
    }

    public function getCurrentRevision(): int
    {
        if (preg_match('/-Rev\.(\d+)$/', $this->number, $match)) {
            return (int) $match[1];
        }

        return 0;
    }
}
