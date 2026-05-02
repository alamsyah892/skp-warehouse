<?php

namespace App\Models;

use App\Enums\GoodsReceiveStatus;
use App\Enums\GoodsReceiveType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\HasDocumentNumber;
use App\Models\Concerns\HasDocumentRevision;
use App\Models\Concerns\HasStateMachine;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceive extends Model
{
    /** @use HasFactory<\Database\Factories\GoodsReceiveFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString, HasDocumentNumber, HasStateMachine, HasDocumentRevision;

    /**
     * Properties & Casts
     */
    protected $fillable = [
        'purchase_order_id',
        'company_id',
        'warehouse_id',
        'warehouse_address_id',
        'division_id',
        'project_id',
        'user_id',

        'type',
        'number',
        'description',
        'delivery_order',
        'notes',
        'info',
        'status',
    ];

    protected array $defaultEmptyStringFields = [
        'number',
        'description',
        'delivery_order',
        'notes',
        'info',
    ];

    protected $casts = [
        'type' => GoodsReceiveType::class,
        'status' => GoodsReceiveStatus::class,
    ];

    /**
     * Constants
     */
    public const MODEL_ALIAS = 'GR';
    public const SELECTABLE_PURCHASE_ORDER_STATUSES = [
        PurchaseOrderStatus::ORDERED,
    ];

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

        static::creating(function (self $record): void {
            $record->user_id = auth()->id();
            $record->status = GoodsReceiveStatus::RECEIVED;

            $record->loadMissing([
                'division',
                'project',
            ]);

            $record->number = self::generateNumber($record);
        });

        static::created(function (self $record): void {
            $record->setStatusLog(GoodsReceiveStatus::RECEIVED);
        });
    }

    /**
     * Relationships
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

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

    public function goodsReceiveItems(): HasMany
    {
        return $this->hasMany(GoodsReceiveItem::class)->orderBy('sort');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(GoodsReceiveStatusLog::class);
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
        return GoodsReceiveStatus::class;
    }

    protected function canUserTransition($newStatus, $user, array $flow): bool
    {
        if (
            $this->hasStatus(GoodsReceiveStatus::RECEIVED) &&
            in_array($newStatus, [
                GoodsReceiveStatus::CANCELED,
                GoodsReceiveStatus::RETURNED,
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
    public function setStatusLog($newStatus, $oldStatus = null, string $note = ''): void
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
    public function hasStatus(GoodsReceiveStatus $status): bool
    {
        return $this->status === $status;
    }

    public function getTotalReceivedQty(): float
    {
        return (float) ($this->getAttribute('goods_receive_items_sum_qty') ?? 0);
    }

    public function scopeWithQuantitySummary(Builder $query): Builder
    {
        return $query->withSum('goodsReceiveItems', 'qty');
    }

    /**
     * Revision Hooks (for HasDocumentRevision)
     */
    protected function getWatchedFields(): array
    {
        return [
            'purchase_order_id',
            'warehouse_address_id',
            'description',
            'delivery_order',
            'type',
        ];
    }

    protected function getRevisionItemsRelation(): ?string
    {
        return 'goodsReceiveItems';
    }

    protected function mapRevisionItem($item): array
    {
        return [
            'purchase_order_item_id' => (int) $item->purchase_order_item_id,
            'item_id' => (int) $item->item_id,
            'qty' => (float) $item->qty,
            'description' => trim((string) $item->description),
        ];
    }

    protected function mapRevisionItemFromArray(array $item): array
    {
        return [
            'purchase_order_item_id' => (int) ($item['purchase_order_item_id'] ?? 0),
            'item_id' => (int) ($item['item_id'] ?? 0),
            'qty' => (float) ($item['qty'] ?? 0),
            'description' => trim((string) ($item['description'] ?? '')),
        ];
    }
}
