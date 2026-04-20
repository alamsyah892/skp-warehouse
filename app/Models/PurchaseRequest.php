<?php

namespace App\Models;

use App\Enums\PurchaseRequestStatus;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\HasDocumentNumber;
use App\Models\Concerns\HasDocumentRevision;
use App\Models\Concerns\HasStateMachine;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseRequestFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString, HasDocumentNumber, HasStateMachine, HasDocumentRevision;


    /** 
     * Properties & Casts 
     */
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

    protected $casts = [
        'status' => PurchaseRequestStatus::class,
    ];


    /**
     * Constants
     */
    public const MODEL_ALIAS = 'BPPB';
    public const TYPE_PURCHASE_REQUEST = 1;


    /**
     * Booted / Events
     */
    protected static function booted(): void
    {
        static::addGlobalScope('user_warehouses', function ($builder) {
            if ($user = auth()->user()) {
                $userWarehouseIds = $user->warehouses()->pluck('warehouses.id');

                if ($userWarehouseIds->isNotEmpty()) {
                    $builder->whereIn('warehouse_id', $userWarehouseIds);
                }
            }
        });

        static::creating(function ($record) {
            $record->user_id = auth()->id();
            $record->type = self::TYPE_PURCHASE_REQUEST;

            $record->loadMissing([
                // 'warehouse',
                // 'company',
                'division',
                'project',
            ]);
            $record->number = self::generateNumber($record);

            $record->status = PurchaseRequestStatus::DRAFT;
        });

        static::created(function ($record) {
            $record->setStatusLog(PurchaseRequestStatus::DRAFT);
        });
    }


    /**
     * Relationships
     */
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

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PurchaseRequestStatusLog::class);
    }

    public function purchaseOrders(): BelongsToMany
    {
        return $this->belongsToMany(PurchaseOrder::class);
    }


    /**
     * Core Logic (State Machine)
     */
    protected function getStatusField(): string
    {
        return 'status';
    }

    protected function getStatusEnumClass(): string
    {
        return PurchaseRequestStatus::class;
    }

    protected function canUserTransition($newStatus, $user, array $flow): bool
    {
        // owner rule
        if (
            $this->hasStatus(PurchaseRequestStatus::DRAFT) &&
            in_array($newStatus, [
                PurchaseRequestStatus::CANCELED,
                PurchaseRequestStatus::REQUESTED,
            ], true) &&
            $this->user_id === $user->id
        ) {
            return true;
        }

        return $user->hasAnyRole($flow[$newStatus->value] ?? []);
    }


    /** 
     * Side Effects
     */
    public function setStatusLog($newStatus, $oldStatus = null, string $note = '')
    {
        $newStatus = $this->normalizeStatus($newStatus);
        $oldStatus = $this->normalizeStatus($oldStatus);

        $this->statusLogs()->create([
            'user_id' => auth()->id(),
            'from_status' => $oldStatus?->value,
            'to_status' => $newStatus->value,
            'note' => $note,
        ]);
    }


    /**
     * Public Helpers
     */
    public function hasStatus(PurchaseRequestStatus $status): bool
    {
        return $this->status === $status;
    }


    /**
     * Revision Hooks (for HasDocumentRevision)
     */
    protected function getWatchedFields(): array
    {
        return [
            'warehouse_address_id',

            'description',
            'memo',
            'boq',
        ];
    }

    protected function getRevisionItemsRelation(): ?string
    {
        return 'purchaseRequestItems';
    }

    protected function mapRevisionItem($item): array
    {
        return [
            'item_id' => (int) $item->item_id,
            'qty' => (float) $item->qty,
            'description' => trim((string) $item->description),
        ];
    }

    protected function mapRevisionItemFromArray(array $item): array
    {
        return [
            'item_id' => (int) ($item['item_id'] ?? 0),
            'qty' => (float) ($item['qty'] ?? 0),
            'description' => trim((string) ($item['description'] ?? '')),
        ];
    }
}
