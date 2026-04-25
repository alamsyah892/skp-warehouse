<?php

namespace App\Models;

use App\Enums\PurchaseOrderType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderTaxType;
use App\Enums\PurchaseRequestStatus;
use App\Enums\GoodsReceiveStatus;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\HasDocumentNumber;
use App\Models\Concerns\HasDocumentRevision;
use App\Models\Concerns\HasStateMachine;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PurchaseOrder extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseOrderFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString, HasDocumentNumber, HasStateMachine, HasDocumentRevision;


    /** 
     * Properties & Casts 
     */
    protected $fillable = [
        'number',
        'type',
        'description',
        'status',

        'vendor_id',
        'company_id',
        'warehouse_id',
        'division_id',
        'project_id',
        'warehouse_address_id',
        'user_id',

        'delivery_date',
        'delivery_notes',
        'shipping_cost',
        'shipping_method',

        'notes',
        'terms',
        'info',

        'discount',
        'tax_type',
        'tax_percentage',
        'tax_description',
        'rounding',
    ];

    protected array $defaultEmptyStringFields = [
        'number',
        'description',

        'delivery_notes',
        'shipping_method',

        'notes',
        'terms',
        'info',

        'tax_type',
        'tax_description',
    ];

    protected $casts = [
        'type' => PurchaseOrderType::class,
        'status' => PurchaseOrderStatus::class,

        'delivery_date' => 'datetime:Y-m-d',
        'shipping_cost' => 'decimal:2',

        'discount' => 'decimal:2',
        'tax_type' => PurchaseOrderTaxType::class,
        'tax_percentage' => 'decimal:2',
        'rounding' => 'decimal:2',
    ];


    /**
     * Constants
     */
    public const MODEL_ALIAS = 'PO';
    public const DEFAULT_TAX_PERCENTAGE = 11;
    public const SELECTABLE_PURCHASE_REQUEST_STATUSES = [
        PurchaseRequestStatus::APPROVED,
        PurchaseRequestStatus::ORDERED,
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

        static::creating(function (self $record) {
            $record->user_id = auth()->id();
            $record->type ??= PurchaseOrderType::RED;

            $record->loadMissing([
                'division',
                'project',
            ]);
            $record->number = self::generateNumber($record);

            $record->status = PurchaseOrderStatus::DRAFT;
        });

        static::created(function (self $record) {
            $record->setStatusLog(PurchaseOrderStatus::DRAFT, note: (string) $record->number);
        });
    }


    /**
     * Relationships
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function warehouseAddress(): BelongsTo
    {
        return $this->belongsTo(WarehouseAddress::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('sort');
    }

    public function purchaseRequestItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->with('purchaseRequestItem');
    }

    public function purchaseRequests(): BelongsToMany
    {
        return $this->belongsToMany(PurchaseRequest::class);
    }

    public function goodsReceives(): HasMany
    {
        return $this->hasMany(GoodsReceive::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PurchaseOrderStatusLog::class);
    }


    public function getSubtotalAmount(): float
    {
        return (float) ($this->getCalculatedSummary()['subtotal'] ?? 0.0);
    }

    public function getNetSubtotalAmount(): float
    {
        return (float) ($this->getCalculatedSummary()['subtotal_after_discount'] ?? 0.0);
    }

    public function getTaxAmount(): float
    {
        return self::calculateSubtotalTax(
            $this->getCalculationItems(),
            $this->discount,
            $this->tax_type,
            $this->tax_percentage,
        );
    }

    public function getTotalAmount(): float
    {
        return self::calculateTotal(
            $this->getCalculationItems(),
            $this->discount,
            $this->tax_type,
            $this->tax_percentage,
        );
    }

    public function getGrandTotalAmount(): float
    {
        return self::calculateGrandTotal(
            $this->getCalculationItems(),
            $this->discount,
            $this->tax_type,
            $this->tax_percentage,
            $this->rounding,
        );
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
        return PurchaseOrderStatus::class;
    }

    protected function canUserTransition($newStatus, $user, array $flow): bool
    {
        if (
            $this->hasStatus(PurchaseOrderStatus::DRAFT) &&
            in_array($newStatus, [
                PurchaseOrderStatus::CANCELED,
                PurchaseOrderStatus::ORDERED
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
    public function hasStatus(PurchaseOrderStatus $status): bool
    {
        return $this->status === $status;
    }

    public function markAsOrdered(?string $note = ''): void
    {
        DB::transaction(function () use ($note): void {
            $this->loadMissing('purchaseRequests');
            $oldPurchaseOrderStatus = $this->status;

            if ($oldPurchaseOrderStatus !== PurchaseOrderStatus::ORDERED) {
                if ($oldPurchaseOrderStatus !== PurchaseOrderStatus::DRAFT) {
                    throw new \RuntimeException("Invalid purchase order status transition");
                }

                $updatedPurchaseOrder = static::query()
                    ->whereKey($this->id)
                    ->where('status', $oldPurchaseOrderStatus->value)
                    ->update(['status' => PurchaseOrderStatus::ORDERED->value]);

                if ($updatedPurchaseOrder === 0) {
                    throw new \RuntimeException("Status has been changed by another user.");
                }

                $this->status = PurchaseOrderStatus::ORDERED;
                $this->setStatusLog(PurchaseOrderStatus::ORDERED, $oldPurchaseOrderStatus, $note);
            }

            $this->purchaseRequests
                ->each(function (PurchaseRequest $purchaseRequest) use ($note): void {
                    if ($purchaseRequest->status === PurchaseRequestStatus::ORDERED) {
                        return;
                    }

                    if ($purchaseRequest->status !== PurchaseRequestStatus::APPROVED) {
                        throw new \RuntimeException("Invalid purchase request status transition");
                    }

                    $updatedPurchaseRequest = PurchaseRequest::query()
                        ->whereKey($purchaseRequest->id)
                        ->where('status', PurchaseRequestStatus::APPROVED->value)
                        ->update(['status' => PurchaseRequestStatus::ORDERED->value]);

                    if ($updatedPurchaseRequest === 0) {
                        throw new \RuntimeException("Purchase request status has been changed by another user.");
                    }

                    $purchaseRequest->status = PurchaseRequestStatus::ORDERED;
                    $purchaseRequest->setStatusLog(PurchaseRequestStatus::ORDERED, PurchaseRequestStatus::APPROVED, $note);
                });
        });
    }

    public function hasGoodsReceivesAllNotCanceled(): bool
    {
        $this->loadMissing('goodsReceives:id,purchase_order_id,status');

        return $this->goodsReceives->isNotEmpty()
            && $this->goodsReceives->every(
                fn(GoodsReceive $goodsReceive): bool => $goodsReceive->status !== GoodsReceiveStatus::CANCELED
            );
    }

    public function hasRemainingGoodsReceiveQty(): bool
    {
        $this->loadMissing('purchaseOrderItems');

        return $this->purchaseOrderItems->contains(
            fn(PurchaseOrderItem $item): bool => round($item->getReceivedQty(), 2) < round((float) $item->qty, 2)
        );
    }


    public static function getCompatiblePurchaseRequestsQuery(?array $header = null): Builder
    {
        return PurchaseRequest::query()
            ->when($header, function (Builder $query) use ($header): void {
                $query
                    ->where('warehouse_id', $header['warehouse_id'])
                    ->where('company_id', $header['company_id'])
                    ->where('division_id', $header['division_id'])
                    ->where('project_id', $header['project_id'])
                ;
            })
        ;
    }

    public static function getCompatiblePurchaseRequestItemsQuery(array $purchaseRequestIds = []): Builder
    {
        return PurchaseRequestItem::query()
            ->with([
                'item:id,code,name,unit',
                'purchaseRequest:id,number,warehouse_id,company_id,division_id,project_id,status',
            ])
            ->whereHas('purchaseRequest', function (Builder $query) use ($purchaseRequestIds) {
                if (blank($purchaseRequestIds)) {
                    return;
                }

                $query->whereIn('id', $purchaseRequestIds);
            })
        ;
    }

    public static function normalizePurchaseRequestIds(array $purchaseRequestIds): array
    {
        return collect($purchaseRequestIds)
            ->flatten()
            ->filter()
            ->map(fn($id): int => (int) $id)
            ->unique()
            ->values()
            ->all()
        ;
    }

    public static function extractHeaderFromPurchaseRequests(array $purchaseRequestIds): ?array
    {
        $ids = self::normalizePurchaseRequestIds($purchaseRequestIds);

        if (blank($ids)) {
            return null;
        }

        $rows = PurchaseRequest::query()
            ->whereIn('id', $ids)
            ->get([
                'id',
                'warehouse_id',
                'company_id',
                'division_id',
                'project_id',
            ])
        ;

        if ($rows->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'purchaseRequests' => __('purchase-order.validation.source_purchase_request_not_found'),
            ]);
        }

        $first = $rows->first();

        $isCompatible = $rows->every(function (PurchaseRequest $purchaseRequest) use ($first): bool {
            return $purchaseRequest->warehouse_id === $first->warehouse_id
                && $purchaseRequest->company_id === $first->company_id
                && $purchaseRequest->division_id === $first->division_id
                && $purchaseRequest->project_id === $first->project_id
            ;
        });

        if (!$isCompatible) {
            throw ValidationException::withMessages([
                'purchaseRequests' => __('purchase-order.validation.incompatible_purchase_requests'),
            ]);
        }

        return [
            'warehouse_id' => $first->warehouse_id,
            'company_id' => $first->company_id,
            'division_id' => $first->division_id,
            'project_id' => $first->project_id,
        ];
    }

    public static function extractHeaderFromPurchaseRequestItems(array $items): ?array
    {
        $ids = collect($items)
            ->pluck('purchase_request_item_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
        ;

        if ($ids->isEmpty()) {
            return null;
        }

        $rows = PurchaseRequestItem::query()
            ->whereIn('id', $ids)
            ->with('purchaseRequest:id,warehouse_id,company_id,division_id,project_id,warehouse_address_id')
            ->get()
        ;

        if ($rows->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'purchaseOrderItems' => __('purchase-order.validation.source_item_not_found'),
            ]);
        }

        $first = $rows->first()->purchaseRequest;

        $isCompatible = $rows->every(function (PurchaseRequestItem $item) use ($first): bool {
            return $item->purchaseRequest
                && $item->purchaseRequest->warehouse_id === $first->warehouse_id
                && $item->purchaseRequest->company_id === $first->company_id
                && $item->purchaseRequest->division_id === $first->division_id
                && $item->purchaseRequest->project_id === $first->project_id
            ;
        });

        if (!$isCompatible) {
            throw ValidationException::withMessages([
                'purchaseOrderItems' => __('purchase-order.validation.incompatible_headers'),
            ]);
        }

        return [
            'warehouse_id' => $first->warehouse_id,
            'company_id' => $first->company_id,
            'division_id' => $first->division_id,
            'project_id' => $first->project_id,
        ];
    }

    public static function validateAllocationQuantities(array $items, ?int $currentPurchaseOrderId = null): void
    {
        $ids = collect($items)
            ->pluck('purchase_request_item_id')
            ->filter()
            ->map(fn($id): int => (int) $id)
            ->filter(fn(int $id): bool => $id > 0)
            ->unique()
            ->values();

        /** @var Collection<int, PurchaseRequestItem> $purchaseRequestItems */
        $purchaseRequestItems = $ids->isEmpty()
            ? collect()
            : PurchaseRequestItem::query()
                ->whereIn('id', $ids->all())
                ->get()
                ->keyBy('id');

        foreach ($items as $index => $item) {
            $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);

            if ($purchaseRequestItemId <= 0) {
                continue;
            }

            /** @var PurchaseRequestItem|null $purchaseRequestItem */
            $purchaseRequestItem = $purchaseRequestItems->get($purchaseRequestItemId);

            if (!$purchaseRequestItem) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.purchase_request_item_id" => __('purchase-order.validation.source_item_not_found'),
                ]);
            }

            $remaining = $purchaseRequestItem->getRemainingQty($currentPurchaseOrderId);

            if ($qty > $remaining) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.qty" => __('purchase-order.validation.qty_exceeded', [
                        'remaining' => number_format($remaining, 2),
                    ]),
                ]);
            }
        }
    }

    public static function validateDuplicatePurchaseRequestItemSources(array $items): void
    {
        $duplicates = collect($items)
            ->pluck('purchase_request_item_id')
            ->filter()
            ->map(fn($id): int => (int) $id)
            ->filter(fn(int $id): bool => $id > 0)
            ->countBy()
            ->filter(fn(int $count): bool => $count > 1);

        if ($duplicates->isNotEmpty()) {
            throw ValidationException::withMessages([
                'purchaseOrderItems' => 'Purchase request item tidak boleh dipilih lebih dari satu kali.',
            ]);
        }
    }

    public static function validateItemsBelongToPurchaseRequests(array $items, array $purchaseRequestIds): void
    {
        $purchaseRequestIds = self::normalizePurchaseRequestIds($purchaseRequestIds);

        $ids = collect($items)
            ->pluck('purchase_request_item_id')
            ->filter()
            ->map(fn($id): int => (int) $id)
            ->filter(fn(int $id): bool => $id > 0)
            ->unique()
            ->values();

        /** @var Collection<int, PurchaseRequestItem> $purchaseRequestItems */
        $purchaseRequestItems = $ids->isEmpty()
            ? collect()
            : PurchaseRequestItem::query()
                ->select(['id', 'purchase_request_id'])
                ->whereIn('id', $ids->all())
                ->get()
                ->keyBy('id');

        foreach ($items as $index => $item) {
            $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);

            if ($purchaseRequestItemId <= 0) {
                continue;
            }

            /** @var PurchaseRequestItem|null $purchaseRequestItem */
            $purchaseRequestItem = $purchaseRequestItems->get($purchaseRequestItemId);

            if (!$purchaseRequestItem) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.purchase_request_item_id" => __('purchase-order.validation.source_item_not_found'),
                ]);
            }

            if (!in_array($purchaseRequestItem->purchase_request_id, $purchaseRequestIds, true)) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.purchase_request_item_id" => __('purchase-order.validation.source_item_not_selected_pr'),
                ]);
            }
        }
    }

    public static function buildPurchaseRequestStatusSnapshot(array $purchaseRequestIds): array
    {
        return PurchaseRequest::query()
            ->whereIn('id', self::normalizePurchaseRequestIds($purchaseRequestIds))
            ->get(['id', 'status'])
            ->mapWithKeys(fn(PurchaseRequest $purchaseRequest): array => [
                $purchaseRequest->id => $purchaseRequest->status->value,
            ])
            ->all();
    }

    public static function buildPurchaseRequestItemSnapshot(
        ?int $purchaseRequestItemId,
        ?int $exceptPurchaseOrderId = null,
    ): ?array {
        if (!$purchaseRequestItemId) {
            return null;
        }

        $purchaseRequestItem = PurchaseRequestItem::query()->find($purchaseRequestItemId);

        if (!$purchaseRequestItem) {
            return null;
        }

        return [
            'request_qty' => round((float) $purchaseRequestItem->qty, 2),
            'ordered_qty' => round($purchaseRequestItem->getOrderedQty($exceptPurchaseOrderId), 2),
        ];
    }

    /**
     * @param  array<int, int|string|null>  $purchaseRequestItemIds
     * @return array<int, array{request_qty: float, ordered_qty: float}>
     */
    public static function buildPurchaseRequestItemSnapshots(array $purchaseRequestItemIds, ?int $exceptPurchaseOrderId = null): array
    {
        $ids = collect($purchaseRequestItemIds)
            ->filter()
            ->map(fn($id): int => (int) $id)
            ->filter(fn(int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        /** @var Collection<int, PurchaseRequestItem> $items */
        $items = PurchaseRequestItem::query()
            ->whereIn('id', $ids->all())
            ->get(['id', 'qty'])
            ->keyBy('id');

        return $ids
            ->mapWithKeys(function (int $id) use ($items, $exceptPurchaseOrderId): array {
                /** @var PurchaseRequestItem|null $item */
                $item = $items->get($id);

                if (!$item) {
                    return [];
                }

                return [
                    $id => [
                        'request_qty' => round((float) $item->qty, 2),
                        'ordered_qty' => round($item->getOrderedQty($exceptPurchaseOrderId), 2),
                    ],
                ];
            })
            ->all();
    }

    public static function validatePurchaseRequestSynchronization(
        array $purchaseRequestIds,
        array $purchaseRequestStatusSnapshot,
        array $items,
        ?int $currentPurchaseOrderId = null,
    ): void {
        $purchaseRequestIds = self::normalizePurchaseRequestIds($purchaseRequestIds);

        if ($purchaseRequestIds === []) {
            return;
        }

        $purchaseRequests = PurchaseRequest::query()
            ->whereIn('id', $purchaseRequestIds)
            ->get(['id', 'status'])
            ->keyBy('id');

        if ($purchaseRequests->count() !== count($purchaseRequestIds)) {
            throw ValidationException::withMessages([
                'purchaseRequests' => __('purchase-order.validation.source_purchase_request_not_found'),
            ]);
        }

        foreach ($purchaseRequestIds as $purchaseRequestId) {
            /** @var PurchaseRequest $purchaseRequest */
            $purchaseRequest = $purchaseRequests->get($purchaseRequestId);
            $snapshotStatus = isset($purchaseRequestStatusSnapshot[$purchaseRequestId])
                ? (int) $purchaseRequestStatusSnapshot[$purchaseRequestId]
                : null;

            if ($snapshotStatus !== null && $purchaseRequest->status->value !== $snapshotStatus) {
                throw ValidationException::withMessages([
                    'purchaseRequests' => __('purchase-order.validation.purchase_request_status_changed'),
                ]);
            }
        }

        $purchaseRequestItemIds = collect($items)
            ->pluck('purchase_request_item_id')
            ->filter()
            ->map(fn($id): int => (int) $id)
            ->filter(fn(int $id): bool => $id > 0)
            ->unique()
            ->values();

        /** @var Collection<int, PurchaseRequestItem> $purchaseRequestItems */
        $purchaseRequestItems = $purchaseRequestItemIds->isEmpty()
            ? collect()
            : PurchaseRequestItem::query()
                ->whereIn('id', $purchaseRequestItemIds->all())
                ->get(['id', 'qty'])
                ->keyBy('id');

        foreach ($items as $index => $item) {
            $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);

            if ($purchaseRequestItemId <= 0) {
                continue;
            }

            /** @var PurchaseRequestItem|null $purchaseRequestItem */
            $purchaseRequestItem = $purchaseRequestItems->get($purchaseRequestItemId);

            if (!$purchaseRequestItem) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.purchase_request_item_id" => __('purchase-order.validation.source_item_not_found'),
                ]);
            }

            $requestQtySnapshot = round((float) ($item['request_qty_snapshot'] ?? 0), 2);
            $orderedQtySnapshot = round((float) ($item['ordered_qty_snapshot'] ?? 0), 2);
            $currentRequestQty = round((float) $purchaseRequestItem->qty, 2);
            $currentOrderedQty = round($purchaseRequestItem->getOrderedQty($currentPurchaseOrderId), 2);

            if (
                $currentRequestQty !== $requestQtySnapshot ||
                $currentOrderedQty !== $orderedQtySnapshot
            ) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.purchase_request_item_id" => __('purchase-order.validation.purchase_request_item_changed'),
                ]);
            }
        }
    }

    public static function validateManualItems(array $items): void
    {
        foreach ($items as $index => $item) {
            $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);
            $itemId = (int) ($item['item_id'] ?? 0);

            if ($purchaseRequestItemId > 0) {
                continue;
            }

            if ($itemId <= 0) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.item_id" => __('validation.required'),
                ]);
            }

            $manualItem = Item::query()
                ->whereKey($itemId)
                ->whereHas('category', fn(Builder $query): Builder => $query->where('allow_po', true))
                ->exists();

            if (!$manualItem) {
                throw ValidationException::withMessages([
                    "purchaseOrderItems.{$index}.item_id" => __('validation.exists', ['attribute' => 'item']),
                ]);
            }
        }
    }

    public static function syncHeaderFromPurchaseRequests(array &$data): void
    {
        $header = self::extractHeaderFromPurchaseRequests($data['purchaseRequests'] ?? []);

        if (!$header) {
            // Jangan reset ke null jika header tidak ditemukan (PR kosong)
            // Ini agar nilai dari Opsi 2 (input manual) tetap terjaga
            return;
        }

        $data['warehouse_id'] = $header['warehouse_id'];
        $data['company_id'] = $header['company_id'];
        $data['division_id'] = $header['division_id'];
        $data['project_id'] = $header['project_id'];
    }

    public static function syncHeaderFromPurchaseRequestItems(array &$data): void
    {
        $items = $data['purchaseOrderItems'] ?? [];
        $header = self::extractHeaderFromPurchaseRequestItems($items);

        if (!$header) {
            return;
        }

        $data['warehouse_id'] = $header['warehouse_id'];
        $data['company_id'] = $header['company_id'];
        $data['division_id'] = $header['division_id'];
        $data['project_id'] = $header['project_id'];
    }

    public static function syncPurchaseOrderItemsFromPurchaseRequestItems(array &$data): void
    {
        $rawItems = collect($data['purchaseOrderItems'] ?? []);

        $purchaseRequestItemIds = $rawItems
            ->pluck('purchase_request_item_id')
            ->filter()
            ->map(fn($id): int => (int) $id)
            ->filter(fn(int $id): bool => $id > 0)
            ->unique()
            ->values();

        /** @var Collection<int, PurchaseRequestItem> $purchaseRequestItems */
        $purchaseRequestItems = $purchaseRequestItemIds->isEmpty()
            ? collect()
            : PurchaseRequestItem::query()
                ->select(['id', 'item_id'])
                ->whereIn('id', $purchaseRequestItemIds->all())
                ->get()
                ->keyBy('id');

        $items = $rawItems
            ->map(function (array $item) use ($purchaseRequestItems): array {
                $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);

                if ($purchaseRequestItemId <= 0) {
                    return $item;
                }

                /** @var PurchaseRequestItem|null $purchaseRequestItem */
                $purchaseRequestItem = $purchaseRequestItems->get($purchaseRequestItemId);

                if (!$purchaseRequestItem) {
                    return $item;
                }

                $item['item_id'] = $purchaseRequestItem->item_id;

                return $item;
            })
            ->values()
            ->all();

        $data['purchaseOrderItems'] = $items;
    }


    public static function calculateItemTotal(array $item): float
    {
        return max((float) ($item['qty'] ?? 0) * (float) ($item['price'] ?? 0), 0.0);
    }

    public static function calculateSubtotal(array $items): float
    {
        return round(
            collect($items)->sum(fn(array $item): float => self::calculateItemTotal($item)),
            2,
        );
    }

    public function getRoundingAttribute(): float
    {
        return (float) ($this->attributes['rounding'] ?? 0);
    }

    public function setRoundingAttribute(float|int|string|null $value): void
    {
        $this->attributes['rounding'] = round((float) $value, 2);
    }

    public static function calculateTotalSubtotal(
        array $items,
        PurchaseOrderTaxType|string|null $taxType = null,
        float|int|string|null $taxPercentage = null,
    ): float {
        return self::calculateSubtotal($items);
    }

    public static function calculateNetSubtotal(
        array $items,
        float|int|string|null $discount = 0,
    ): float {
        return max(round(self::calculateSubtotal($items) - max((float) $discount, 0.0), 2), 0.0);
    }

    public static function calculateSubtotalDiscount(
        array $items,
        float|int|string|null $discount = 0,
        PurchaseOrderTaxType|string|null $taxType = null,
        float|int|string|null $taxPercentage = null,
    ): float {
        return (float) (self::calculateOrderSummary(
            $items,
            $discount,
            $taxType,
            $taxPercentage,
        )['discount'] ?? 0.0);
    }

    public static function getTaxPercentageOptions(): array
    {
        return collect([10, 11, 12])
            ->mapWithKeys(fn(int $percentage): array => [
                (string) $percentage => "{$percentage}%",
            ])
            ->all()
        ;
    }

    public static function calculateTaxAmount(
        float|int|string|null $netSubtotal,
        PurchaseOrderTaxType|string|null $taxType,
        float|int|string|null $taxPercentage,
    ): float {
        $taxableAmount = max(round((float) $netSubtotal, 2), 0.0);
        $rate = self::resolveIndonesianVatRate($taxPercentage);
        $normalizedTaxType = self::normalizeTaxType($taxType);

        if ($taxableAmount <= 0 || $rate <= 0 || !$normalizedTaxType) {
            return 0.0;
        }

        return match ($normalizedTaxType) {
            PurchaseOrderTaxType::EXCLUDE => round($taxableAmount * ($rate / 100), 0),
            PurchaseOrderTaxType::INCLUDE => round($taxableAmount - ($taxableAmount / (1 + ($rate / 100))), 0),
        };
    }

    public static function calculateSubtotalTax(
        array $items,
        float|int|string|null $discount = 0,
        PurchaseOrderTaxType|string|null $taxType = null,
        float|int|string|null $taxPercentage = null,
    ): float {
        $summary = self::calculateOrderSummary(
            $items,
            $discount,
            $taxType,
            $taxPercentage,
        );

        return (float) ($summary['tax_amount'] ?? 0.0);
    }

    public static function calculateTotalBeforeRounding(
        array $items,
        float|int|string|null $discount = 0,
        PurchaseOrderTaxType|string|null $taxType = null,
        float|int|string|null $taxPercentage = null,
    ): float {
        return (float) (self::calculateOrderSummary(
            $items,
            $discount,
            $taxType,
            $taxPercentage,
        )['total_before_rounding'] ?? 0.0);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{
     *     subtotal: float,
     *     discount: float,
     *     subtotal_after_discount: float,
     *     dpp: float,
     *     tax_amount: float,
     *     total_before_rounding: float,
     *     rounding: float,
     *     grand_total: float
     * }
     */
    public static function calculateOrderSummary(
        array $items,
        float|int|string|null $orderDiscount = 0,
        PurchaseOrderTaxType|string|null $taxType = null,
        float|int|string|null $taxPercentage = null,
        float|int|string|null $rounding = 0,
    ): array {
        return self::calculateOrderBreakdown(
            $items,
            $orderDiscount,
            $taxType,
            $taxPercentage,
            $rounding,
        )['summary'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{
     *     summary: array{
     *         subtotal: float,
     *         discount: float,
     *         subtotal_after_discount: float,
     *         dpp: float,
     *         tax_amount: float,
     *         total_before_rounding: float,
     *         rounding: float,
     *         grand_total: float
     *     },
     *     lines: array<int|string, array{
     *         key: int|string,
     *         subtotal: float,
     *         discount: float,
     *         subtotal_after_discount: float,
     *         dpp: float,
     *         tax_amount: float,
     *         total_before_rounding: float,
     *         grand_total: float
     *     }>
     * }
     */
    public static function calculateOrderBreakdown(
        array $items,
        float|int|string|null $orderDiscount = 0,
        PurchaseOrderTaxType|string|null $taxType = null,
        float|int|string|null $taxPercentage = null,
        float|int|string|null $rounding = 0,
    ): array {
        $subtotal = self::calculateSubtotal($items);
        $discount = min(max(round((float) $orderDiscount, 2), 0.0), $subtotal);
        $subtotalAfterDiscount = max(round($subtotal - $discount, 2), 0.0);
        $normalizedTaxType = self::normalizeTaxType($taxType);
        $taxAmount = self::calculateTaxAmount($subtotalAfterDiscount, $normalizedTaxType, $taxPercentage);
        $dpp = $normalizedTaxType === PurchaseOrderTaxType::INCLUDE
            ? max(round($subtotalAfterDiscount - $taxAmount, 2), 0.0)
            : $subtotalAfterDiscount;
        $totalBeforeRounding = $normalizedTaxType === PurchaseOrderTaxType::EXCLUDE
            ? round($dpp + $taxAmount, 2)
            : $subtotalAfterDiscount;
        $rounding = round((float) $rounding, 2);

        $lines = collect($items)
            ->mapWithKeys(function (array $item, int|string $index): array {
                $lineKey = $item['line_key'] ?? $item['id'] ?? $item['purchase_request_item_id'] ?? $index;
                $lineSubtotal = round(self::calculateItemTotal($item), 2);

                return [
                    $lineKey => [
                        'key' => $lineKey,
                        'subtotal' => $lineSubtotal,
                        'discount' => 0.0,
                        'subtotal_after_discount' => $lineSubtotal,
                        'dpp' => $lineSubtotal,
                        'tax_amount' => 0.0,
                        'total_before_rounding' => $lineSubtotal,
                        'grand_total' => $lineSubtotal,
                    ]
                ];
            })
            ->all();

        return [
            'summary' => [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'subtotal_after_discount' => $subtotalAfterDiscount,
                'dpp' => $dpp,
                'tax_amount' => $taxAmount,
                'total_before_rounding' => $totalBeforeRounding,
                'rounding' => $rounding,
                'grand_total' => max(round($totalBeforeRounding + $rounding, 2), 0.0),
            ],
            'lines' => $lines,
        ];
    }

    public static function resolveIndonesianVatRate(float|int|string|null $taxPercentage): float
    {
        $rate = round(max((float) $taxPercentage, 0.0), 2);

        return $rate === 12.0 ? 11.0 : $rate;
    }

    public static function calculateTotal(
        array $items,
        float|int|string|null $discount,
        PurchaseOrderTaxType|string|null $taxType,
        float|int|string|null $taxPercentage,
    ): float {
        return self::calculateOrderSummary(
            $items,
            $discount,
            $taxType,
            $taxPercentage,
        )['total'];
    }

    public static function calculateGrandTotal(
        array $items,
        float|int|string|null $discount,
        PurchaseOrderTaxType|string|null $taxType,
        float|int|string|null $taxPercentage,
        float|int|string|null $rounding,
    ): float {
        return self::calculateOrderSummary(
            $items,
            $discount,
            $taxType,
            $taxPercentage,
            $rounding,
        )['grand_total'];
    }

    public static function syncTaxTotals(array &$data): void
    {
        // $data['tax'] = self::calculateOrderSummary(
        //     $data['purchaseOrderItems'] ?? [],
        //     $data['discount'] ?? 0,
        //     $data['tax_type'] ?? null,
        //     $data['tax_percentage'] ?? null,
        //     $data['rounding'] ?? 0,
        // )['tax_amount'];
    }

    public function syncCalculatedTotals(): void
    {
        $items = $this->purchaseOrderItems()
            ->get()
            ->map(fn(PurchaseOrderItem $item): array => [
                'id' => $item->id,
                'purchase_request_item_id' => $item->purchase_request_item_id,
                'qty' => (float) $item->qty,
                'price' => (float) $item->price,
            ])
            ->all();

        // $taxAmount = self::calculateOrderSummary(
        //     $items,
        //     $this->discount,
        //     $this->tax_type,
        //     $this->tax_percentage,
        //     $this->rounding,
        // )['tax_amount'];

        // $this->forceFill([
        //     'tax' => $taxAmount,
        // ])->saveQuietly();
    }

    public static function normalizeTaxType(PurchaseOrderTaxType|string|null $taxType): ?PurchaseOrderTaxType
    {
        if ($taxType instanceof PurchaseOrderTaxType) {
            return $taxType;
        }

        if (blank($taxType)) {
            return null;
        }

        return PurchaseOrderTaxType::tryFrom((string) $taxType);
    }

    /**
     * @return array<int, array<string, int|float|null>>
     */
    protected function getCalculationItems(): array
    {
        return $this->purchaseOrderItems->map(fn(PurchaseOrderItem $item): array => [
            'id' => $item->id,
            'purchase_request_item_id' => $item->purchase_request_item_id,
            'qty' => (float) $item->qty,
            'price' => (float) $item->price,
        ])->all();
    }

    /**
     * @return array{
     *     subtotal: float,
     *     discount: float,
     *     subtotal_after_discount: float,
     *     dpp: float,
     *     tax_amount: float,
     *     total_before_rounding: float,
     *     rounding: float,
     *     grand_total: float
     * }
     */
    protected function getCalculatedSummary(): array
    {
        return self::calculateOrderSummary(
            $this->getCalculationItems(),
            $this->discount,
            $this->tax_type,
            $this->tax_percentage,
            $this->rounding,
        );
    }


    /**
     * Revision Hooks (for HasDocumentRevision)
     */
    protected function getWatchedFields(): array
    {
        return [
            'description',

            'vendor_id',
            'warehouse_address_id',

            'delivery_date',
            'delivery_notes',
            'shipping_cost',
            'shipping_method',

            'terms',

            'discount',
            'tax_type',
            'tax_percentage',
            'tax_description',
            'rounding',
        ];
    }

    protected function getRevisionItemsRelation(): ?string
    {
        return 'purchaseOrderItems';
    }

    protected function mapRevisionItem($item): array
    {
        return [
            'purchase_request_item_id' => (int) $item->purchase_request_item_id,
            'item_id' => (int) $item->item_id,
            'qty' => (float) $item->qty,
            'price' => (float) $item->price,
            'description' => trim((string) $item->description),
        ];
    }

    protected function mapRevisionItemFromArray(array $item): array
    {
        return [
            'purchase_request_item_id' => (int) ($item['purchase_request_item_id'] ?? 0),
            'item_id' => (int) ($item['item_id'] ?? 0),
            'qty' => (float) ($item['qty'] ?? 0),
            'price' => (float) ($item['price'] ?? 0),
            'description' => trim((string) ($item['description'] ?? '')),
        ];
    }

    protected static function generateNumber($record): string
    {
        return DB::transaction(function () use ($record): string {
            $year = now()->format('y');
            $month = now()->format('m');
            $project = $record->project ?? $record->project()->first();
            $division = $record->division ?? $record->division()->first();
            $type = $record->type instanceof PurchaseOrderType
                ? $record->type
                : PurchaseOrderType::tryFrom((int) $record->type);

            if (!$project || !$division || !$type) {
                throw new RuntimeException('Document number cannot be generated without type, project, and division.');
            }

            $prefix = sprintf(
                '%s/%s/%s/%s/%s',
                $type->initial(),
                $year,
                $month,
                $project->po_code,
                $division->code,
            );

            $last = static::withTrashed()
                ->where('number', 'like', "{$prefix}/%")
                ->lockForUpdate()
                ->orderByDesc('number')
                ->value('number');

            $lastSequence = 0;

            if ($last && preg_match('/\/(\d{3})(?:-Rev\.\d+)?$/', $last, $match)) {
                $lastSequence = (int) $match[1];
            }

            $sequence = str_pad($lastSequence + 1, 3, '0', STR_PAD_LEFT);

            return "{$prefix}/{$sequence}";
        });
    }
}
