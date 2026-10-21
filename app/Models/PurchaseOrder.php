<?php

namespace App\Models;

use App\Enums\GoodsReceiveStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderTaxType;
use App\Enums\PurchaseOrderType;
use App\Enums\PurchaseRequestStatus;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\HasDocumentNumber;
use App\Models\Concerns\HasDocumentRevision;
use App\Models\Concerns\HasStateMachine;
use App\Models\Concerns\LogsAllFillable;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property PurchaseOrderStatus $status
 */
class PurchaseOrder extends Model
{
    use DefaultEmptyString, HasDocumentNumber, HasDocumentRevision, LogsAllFillable;

    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    use HasStateMachine {
        changeStatus as protected changeStateStatus;
    }
    use SoftDeletes;

    /**
     * Properties & Casts
     */
    protected $fillable = [
        'number',
        'type',
        'description',
        'status',

        'company_id',
        'warehouse_id',
        'division_id',
        'project_id',

        'vendor_id',

        'warehouse_address_id',

        'user_id',

        'po_opening_letter',
        'po_payment_terms',
        'po_processing_date',
        'po_payment_method',
        'po_description',
        'po_terms',
        'po_billing_method',
        'po_opname_method',
        'po_processing_time',
        'po_location',
        'po_miscellaneous',
        'po_warranty',
        'po_duration_info',
        'po_carbon_copy',
        'po_footnote',

        'notes',
        'info',

        'discount',
        'tax_type',
        'tax_percentage',
        'tax_description',
        'rounding',
        'total_amount',
    ];

    protected array $defaultEmptyStringFields = [
        'number',
        'description',

        'po_opening_letter',
        'po_payment_terms',
        'po_processing_date',
        'po_payment_method',
        'po_description',
        'po_terms',
        'po_billing_method',
        'po_opname_method',
        'po_processing_time',
        'po_location',
        'po_miscellaneous',
        'po_warranty',
        'po_duration_info',
        'po_carbon_copy',
        'po_footnote',

        'notes',
        'info',

        'tax_type',
        'tax_description',
    ];

    protected $casts = [
        'type' => PurchaseOrderType::class,
        'status' => PurchaseOrderStatus::class,

        'discount' => 'decimal:2',
        'tax_type' => PurchaseOrderTaxType::class,
        'rounding' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Constants
     */
    public const MODEL_ALIAS = 'PO';

    public const DEFAULT_TAX_PERCENTAGE = 11;

    public const SELECTABLE_PURCHASE_REQUEST_STATUSES = [
        PurchaseRequestStatus::REVIEWED,
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
            $record->user_id = auth()->id() ?? $record->user_id;
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
        return $this->belongsToMany(PurchaseRequest::class)->withTrashed();
    }

    public function goodsReceives(): HasMany
    {
        return $this->hasMany(GoodsReceive::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PurchaseOrderStatusLog::class);
    }

    /**
     * Public Helpers
     */
    public function hasStatus(PurchaseOrderStatus $status): bool
    {
        return $this->status === $status;
    }

    public function getTotalOrderedQty(): float
    {
        return (float) ($this->getAttribute('purchase_order_items_sum_qty') ?? 0);
    }

    public function getTotalReceivedQty(): float
    {
        return (float) ($this->getAttribute('purchase_order_items_received_qty_sum') ?? 0);
    }

    public function getReceivedPercentage(): float
    {
        $receivedPercentage = $this->getAttribute('purchase_order_items_received_percentage');

        if ($receivedPercentage !== null) {
            return (float) $receivedPercentage;
        }

        $totalOrderedQty = $this->getTotalOrderedQty();

        if ($totalOrderedQty <= 0) {
            return 0.0;
        }

        return round(($this->getTotalReceivedQty() / $totalOrderedQty) * 100, 2);
    }

    public function changeStatus($newStatus, ?string $note = ''): void
    {
        $normalizedStatus = $newStatus instanceof PurchaseOrderStatus
            ? $newStatus
            : PurchaseOrderStatus::from($newStatus);

        $this->changeStateStatus($normalizedStatus, $note);

        if ($normalizedStatus === PurchaseOrderStatus::ORDERED) {
            $this->syncPurchaseRequestsToOrdered();
        }
    }

    public function scopeWithQuantitySummary(Builder $query): Builder
    {
        return $query
            ->withSum('purchaseOrderItems', 'qty')
            ->selectSub(
                GoodsReceiveItem::query()
                    ->selectRaw('coalesce(sum(goods_receive_items.qty), 0)')
                    ->join('goods_receives', 'goods_receives.id', '=', 'goods_receive_items.goods_receive_id')
                    ->join('purchase_order_items', 'purchase_order_items.id', '=', 'goods_receive_items.purchase_order_item_id')
                    ->whereColumn('purchase_order_items.purchase_order_id', 'purchase_orders.id')
                    ->whereIn('goods_receives.status', [
                        GoodsReceiveStatus::RECEIVED->value,
                        GoodsReceiveStatus::CONFIRMED->value,
                    ]),
                'purchase_order_items_received_qty_sum',
            )
            ->selectSub(
                PurchaseOrderItem::query()
                    ->selectRaw(
                        'case
                            when coalesce(sum(purchase_order_items.qty), 0) <= 0 then 0
                            else round(
                                (
                                    coalesce((
                                        select sum(goods_receive_items.qty)
                                        from goods_receive_items
                                        inner join goods_receives
                                            on goods_receives.id = goods_receive_items.goods_receive_id
                                        inner join purchase_order_items as received_purchase_order_items
                                            on received_purchase_order_items.id = goods_receive_items.purchase_order_item_id
                                        where received_purchase_order_items.purchase_order_id = purchase_orders.id
                                            and goods_receives.status in (?, ?)
                                    ), 0) / coalesce(sum(purchase_order_items.qty), 0)
                                ) * 100,
                                2
                            )
                        end',
                        [
                            GoodsReceiveStatus::RECEIVED->value,
                            GoodsReceiveStatus::CONFIRMED->value,
                        ]
                    )
                    ->whereColumn('purchase_order_items.purchase_order_id', 'purchase_orders.id'),
                'purchase_order_items_received_percentage',
            );
    }

    /**
     * for HasStateMachine
     */
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
                PurchaseOrderStatus::ORDERED,
            ], true) &&
            $this->user_id === $user->id
        ) {
            return true;
        }

        return $user->hasAnyRole($flow[$newStatus->value] ?? []);
    }

    protected function syncPurchaseRequestsToOrdered(): void
    {
        $this->loadMissing('purchaseRequests');

        $this->purchaseRequests
            ->each(function (PurchaseRequest $purchaseRequest): void {
                $purchaseRequest->update([
                    'status' => PurchaseRequestStatus::ORDERED,
                ]);

                $purchaseRequest->setStatusLog(
                    PurchaseRequestStatus::ORDERED,
                    PurchaseRequestStatus::REVIEWED,
                    (string) $this->number,
                );
            });
    }

    /**
     * for HasDocumentRevision
     */
    protected function getWatchedFields(): array
    {
        return [
            'description',

            'vendor_id',
            'warehouse_address_id',

            'po_opening_letter',
            'po_payment_terms',
            'po_processing_date',
            'po_payment_method',
            'po_description',
            'po_terms',
            'po_billing_method',
            'po_opname_method',
            'po_processing_time',
            'po_location',
            'po_miscellaneous',
            'po_warranty',
            'po_duration_info',
            'po_carbon_copy',
            'po_footnote',

            'tax_type',
            'tax_percentage',
            'tax_description',
            'discount',
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
            'sort' => (int) $item->sort,
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
            'sort' => (int) ($item['sort'] ?? 0),
        ];
    }
}
