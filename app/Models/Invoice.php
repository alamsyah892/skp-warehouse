<?php

namespace App\Models;

use App\Enums\PurchaseOrderTaxType;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use DefaultEmptyString, LogsAllFillable;

    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    use SoftDeletes;

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
}
