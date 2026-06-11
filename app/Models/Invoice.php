<?php

namespace App\Models;

use App\Enums\PurchaseOrderTaxType;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString;

    protected $fillable = [
        'vendor_id',
        'goods_receive_id',
        'purchase_order_id',
        'user_id',
        'number',
        'invoice_date',
        'invoice_due_date',
        'discount',
        'tax_type',
        'tax_percentage',
        'rounding',
        'total_amount',
        'notes',
        'info',
    ];

    protected array $defaultEmptyStringFields = [
        'notes',
        'info',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'invoice_due_date' => 'date',
        'discount' => 'decimal:2',
        'tax_type' => PurchaseOrderTaxType::class,
        'tax_percentage' => 'integer',
        'rounding' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $record): void {
            $record->user_id ??= auth()->id();
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function goodsReceive(): BelongsTo
    {
        return $this->belongsTo(GoodsReceive::class)->withTrashed();
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort');
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeSummaryItems(array $items): array
    {
        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'qty' => (float) ($item['qty'] ?? 0),
                'price' => (float) ($item['price'] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return array<string, float>
     */
    public static function calculateSummary(
        array $items,
        float $discount,
        PurchaseOrderTaxType|string|null $taxType,
        int|string|null $taxPercentage,
        float $rounding,
    ): array {
        return PurchaseOrder::calculateOrderSummary(
            self::normalizeSummaryItems($items),
            $discount,
            $taxType,
            $taxPercentage,
            $rounding,
        );
    }
}
