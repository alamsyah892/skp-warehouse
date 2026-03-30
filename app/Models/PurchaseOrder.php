<?php

namespace App\Models;

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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PurchaseOrder extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseOrderFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString, HasDocumentNumber, HasStateMachine, HasDocumentRevision;


    protected $fillable = [
        'vendor_id',
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
        'termin',
        'delivery_info',
        'notes',

        'info',

        'status',

        'discount',
        'tax',
        'tax_description',
        'rounding',
    ];

    protected array $defaultEmptyStringFields = [
        'description',
        'memo',
        'termin',
        'delivery_info',
        'notes',

        'info',

        'tax_description',
    ];

    protected $casts = [
        'status' => PurchaseOrderStatus::class,

        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'rounding' => 'decimal:2',
    ];


    public const MODEL_ALIAS = 'PO';
    public const TYPE_PURCHASE_ORDER = 1;


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
            $record->type = self::TYPE_PURCHASE_ORDER;

            $record->loadMissing([
                'division',
                'project',
            ]);
            $record->number = self::generateNumber($record);

            $record->status = PurchaseOrderStatus::DRAFT;
        });

        static::created(function (self $record) {
            $record->setStatusLog(PurchaseOrderStatus::DRAFT);
        });
    }


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

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PurchaseOrderStatusLog::class);
    }


    public function getSubtotalAmount(): float
    {
        return (float) $this->purchaseOrderItems->sum(
            fn(PurchaseOrderItem $item): float => $item->getLineTotalAmount()
        );
    }

    public function getNetSubtotalAmount(): float
    {
        return max($this->getSubtotalAmount() - (float) $this->discount, 0.0);
    }

    public function getGrandTotalAmount(): float
    {
        return $this->getNetSubtotalAmount()
            + (float) $this->tax
            + (float) $this->rounding;
    }


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
            in_array($newStatus, [PurchaseOrderStatus::CANCELED, PurchaseOrderStatus::ORDERED], true) &&
            $this->user_id === $user->id
        ) {
            return true;
        }

        return $user->hasAnyRole($flow[$newStatus->value] ?? []);
    }


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


    public function hasStatus(PurchaseOrderStatus $status): bool
    {
        return $this->status === $status;
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

                // if (array_key_exists('warehouse_address_id', $header)) {
                //     if ($header['warehouse_address_id']) {
                //         $query->where('warehouse_address_id', $header['warehouse_address_id']);
                //     } else {
                //         $query->whereNull('warehouse_address_id');
                //     }
                // }
            });
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
            });
    }

    public static function normalizePurchaseRequestIds(array $purchaseRequestIds): array
    {
        return collect($purchaseRequestIds)
            ->flatten()
            ->filter()
            ->map(fn($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
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
                // 'warehouse_address_id',
            ]);

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
                // && $purchaseRequest->warehouse_address_id === $first->warehouse_address_id
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
            // 'warehouse_address_id' => $first->warehouse_address_id,
        ];
    }

    public static function extractHeaderFromPurchaseRequestItems(array $items): ?array
    {
        $ids = collect($items)
            ->pluck('purchase_request_item_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return null;
        }

        $rows = PurchaseRequestItem::query()
            ->whereIn('id', $ids)
            ->with('purchaseRequest:id,warehouse_id,company_id,division_id,project_id,warehouse_address_id')
            ->get();

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
                && $item->purchaseRequest->project_id === $first->project_id;
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
            // 'warehouse_address_id' => $first->warehouse_address_id,
        ];
    }

    public static function validateAllocationQuantities(array $items, ?int $currentPurchaseOrderId = null): void
    {
        foreach ($items as $index => $item) {
            $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);

            if ($purchaseRequestItemId <= 0) {
                continue;
            }

            $purchaseRequestItem = PurchaseRequestItem::query()->find($purchaseRequestItemId);

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

    public static function validateItemsBelongToPurchaseRequests(array $items, array $purchaseRequestIds): void
    {
        $purchaseRequestIds = self::normalizePurchaseRequestIds($purchaseRequestIds);

        foreach ($items as $index => $item) {
            $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);

            if ($purchaseRequestItemId <= 0) {
                continue;
            }

            $purchaseRequestItem = PurchaseRequestItem::query()
                ->select(['id', 'purchase_request_id'])
                ->find($purchaseRequestItemId);

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
        // $data['warehouse_address_id'] = $header['warehouse_address_id'];
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
        // $data['warehouse_address_id'] = $header['warehouse_address_id'];
    }

    public static function syncPurchaseOrderItemsFromPurchaseRequestItems(array &$data): void
    {
        $items = collect($data['purchaseOrderItems'] ?? [])
            ->map(function (array $item): array {
                $purchaseRequestItemId = (int) ($item['purchase_request_item_id'] ?? 0);

                if ($purchaseRequestItemId <= 0) {
                    return $item;
                }

                $purchaseRequestItem = PurchaseRequestItem::query()
                    ->select(['id', 'item_id', 'discount'])
                    ->find($purchaseRequestItemId);

                if (!$purchaseRequestItem) {
                    return $item;
                }

                $item['item_id'] = $purchaseRequestItem->item_id;
                $item['discount'] = $purchaseRequestItem->discount;

                return $item;
            })
            ->values()
            ->all();

        $data['purchaseOrderItems'] = $items;
    }

    public static function calculateItemTotal(array $item): float
    {
        $grossAmount = (float) ($item['qty'] ?? 0) * (float) ($item['price'] ?? 0);
        $discountAmount = (float) ($item['discount'] ?? 0);

        return max($grossAmount - $discountAmount, 0.0);
    }

    public static function calculateSubtotal(array $items): float
    {
        return (float) collect($items)
            ->sum(fn(array $item): float => self::calculateItemTotal($item));
    }

    public static function calculateNetSubtotal(array $items, float|int|string|null $discount): float
    {
        return max(self::calculateSubtotal($items) - (float) $discount, 0.0);
    }

    public static function calculateGrandTotal(
        array $items,
        float|int|string|null $discount,
        float|int|string|null $tax,
        float|int|string|null $rounding,
    ): float {
        return self::calculateNetSubtotal($items, $discount)
            + (float) $tax
            + (float) $rounding;
    }


    protected function getWatchedFields(): array
    {
        return [
            'vendor_id',
            'warehouse_address_id',
            'description',
            'memo',
            'termin',
            'discount',
            'tax',
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
            'discount' => (float) $item->discount,
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
            'discount' => (float) ($item['discount'] ?? 0),
            'description' => trim((string) ($item['description'] ?? '')),
        ];
    }
}
