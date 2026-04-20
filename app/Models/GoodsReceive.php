<?php

namespace App\Models;

use App\Enums\GoodsReceiveStatus;
use App\Enums\GoodsReceiveType;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\HasDocumentNumber;
use App\Models\Concerns\HasDocumentRevision;
use App\Models\Concerns\HasStateMachine;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

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
            'notes',
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

    /**
     * Data sync & validation helpers
     */
    public static function syncHeaderFromPurchaseOrder(array &$data): void
    {
        $purchaseOrderId = (int) ($data['purchase_order_id'] ?? 0);

        if ($purchaseOrderId <= 0) {
            return;
        }

        $purchaseOrder = PurchaseOrder::query()->find($purchaseOrderId);

        if (!$purchaseOrder) {
            return;
        }

        $data['warehouse_id'] = $purchaseOrder->warehouse_id;
        $data['company_id'] = $purchaseOrder->company_id;
        $data['division_id'] = $purchaseOrder->division_id;
        $data['project_id'] = $purchaseOrder->project_id;
        $data['warehouse_address_id'] = $purchaseOrder->warehouse_address_id;
    }

    public static function syncGoodsReceiveItemsFromPurchaseOrderItems(array &$data): void
    {
        $rawItems = collect($data['goodsReceiveItems'] ?? []);

        $purchaseOrderItemIds = $rawItems
            ->pluck('purchase_order_item_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        /** @var Collection<int, PurchaseOrderItem> $purchaseOrderItems */
        $purchaseOrderItems = $purchaseOrderItemIds->isEmpty()
            ? collect()
            : PurchaseOrderItem::query()
                ->select(['id', 'item_id'])
                ->whereIn('id', $purchaseOrderItemIds->all())
                ->get()
                ->keyBy('id');

        $data['goodsReceiveItems'] = $rawItems
            ->map(function (array $item) use ($purchaseOrderItems): array {
                $purchaseOrderItemId = (int) ($item['purchase_order_item_id'] ?? 0);

                if ($purchaseOrderItemId <= 0) {
                    return $item;
                }

                /** @var PurchaseOrderItem|null $purchaseOrderItem */
                $purchaseOrderItem = $purchaseOrderItems->get($purchaseOrderItemId);

                if (!$purchaseOrderItem) {
                    return $item;
                }

                $item['item_id'] = $purchaseOrderItem->item_id;

                return $item;
            })
            ->values()
            ->all();
    }

    public static function validatePurchaseOrderItemsBelongToPurchaseOrder(array $items, ?int $purchaseOrderId): void
    {
        $purchaseOrderId = (int) $purchaseOrderId;

        if ($purchaseOrderId <= 0) {
            return;
        }

        $ids = collect($items)
            ->pluck('purchase_order_item_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        /** @var Collection<int, PurchaseOrderItem> $purchaseOrderItems */
        $purchaseOrderItems = PurchaseOrderItem::query()
            ->select(['id', 'purchase_order_id'])
            ->whereIn('id', $ids->all())
            ->get()
            ->keyBy('id');

        foreach ($items as $index => $item) {
            $purchaseOrderItemId = (int) ($item['purchase_order_item_id'] ?? 0);

            if ($purchaseOrderItemId <= 0) {
                continue;
            }

            /** @var PurchaseOrderItem|null $purchaseOrderItem */
            $purchaseOrderItem = $purchaseOrderItems->get($purchaseOrderItemId);

            if (!$purchaseOrderItem) {
                throw ValidationException::withMessages([
                    "goodsReceiveItems.{$index}.purchase_order_item_id" => __('goods-receive.validation.source_item_not_found'),
                ]);
            }

            if ((int) $purchaseOrderItem->purchase_order_id !== $purchaseOrderId) {
                throw ValidationException::withMessages([
                    "goodsReceiveItems.{$index}.purchase_order_item_id" => __('goods-receive.validation.source_item_not_found'),
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, float>
     */
    public static function buildReceivedQtyByPurchaseOrderItemSnapshot(array $purchaseOrderItemIds, ?int $exceptGoodsReceiveId = null): array
    {
        $ids = collect($purchaseOrderItemIds)
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $query = GoodsReceiveItem::query()
            ->selectRaw('purchase_order_item_id, SUM(qty) AS received_qty')
            ->whereIn('purchase_order_item_id', $ids->all())
            ->whereHas('goodsReceive', function ($query) use ($exceptGoodsReceiveId) {
                $query->where('status', GoodsReceiveStatus::RECEIVED->value);

                if ($exceptGoodsReceiveId) {
                    $query->where('id', '!=', $exceptGoodsReceiveId);
                }
            })
            ->groupBy('purchase_order_item_id');

        return $query
            ->get()
            ->mapWithKeys(fn ($row): array => [(int) $row->purchase_order_item_id => (float) $row->received_qty])
            ->all();
    }

    public static function validateAllocationQuantities(array $items, ?int $currentGoodsReceiveId = null): void
    {
        $ids = collect($items)
            ->pluck('purchase_order_item_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        /** @var Collection<int, PurchaseOrderItem> $purchaseOrderItems */
        $purchaseOrderItems = $ids->isEmpty()
            ? collect()
            : PurchaseOrderItem::query()
                ->whereIn('id', $ids->all())
                ->get(['id', 'qty'])
                ->keyBy('id');

        $receivedSnapshot = self::buildReceivedQtyByPurchaseOrderItemSnapshot($ids->all(), $currentGoodsReceiveId);

        foreach ($items as $index => $item) {
            $purchaseOrderItemId = (int) ($item['purchase_order_item_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);

            if ($purchaseOrderItemId <= 0) {
                continue;
            }

            /** @var PurchaseOrderItem|null $purchaseOrderItem */
            $purchaseOrderItem = $purchaseOrderItems->get($purchaseOrderItemId);

            if (!$purchaseOrderItem) {
                throw ValidationException::withMessages([
                    "goodsReceiveItems.{$index}.purchase_order_item_id" => __('goods-receive.validation.source_item_not_found'),
                ]);
            }

            $alreadyReceived = (float) ($receivedSnapshot[$purchaseOrderItemId] ?? 0);
            $remaining = max((float) $purchaseOrderItem->qty - $alreadyReceived, 0.0);

            if ($qty > $remaining) {
                throw ValidationException::withMessages([
                    "goodsReceiveItems.{$index}.qty" => __('goods-receive.validation.qty_exceeded', [
                        'remaining' => number_format($remaining, 2),
                    ]),
                ]);
            }
        }
    }
}
