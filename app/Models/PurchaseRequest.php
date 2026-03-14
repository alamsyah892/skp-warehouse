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

    public const TYPE_PURCHASE = 1;
    public const TYPE_LOAN = 2;

    public const TYPE_LABELS = [
        self::TYPE_PURCHASE => 'Pengajuan Pembelian',
        self::TYPE_LOAN => 'Pengajuan Peminjaman',
    ];

    public const STATUS_DRAFT = 1;
    public const STATUS_CANCELED = 2;
    public const STATUS_WAITING = 3;
    public const STATUS_RECEIVED = 4;
    public const STATUS_ORDERED = 5;
    public const STATUS_FINISH = 6;

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_DRAFT => __('purchase-request.status.draft'),
            self::STATUS_CANCELED => __('purchase-request.status.canceled'),
            self::STATUS_WAITING => __('purchase-request.status.waiting'),
            self::STATUS_RECEIVED => __('purchase-request.status.received'),
            self::STATUS_ORDERED => __('purchase-request.status.ordered'),
            self::STATUS_FINISH => __('purchase-request.status.finish'),
        ];
    }

    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'gray',
        self::STATUS_CANCELED => 'danger',
        self::STATUS_WAITING => 'warning',
        self::STATUS_RECEIVED => 'primary',
        self::STATUS_ORDERED => 'info',
        self::STATUS_FINISH => 'success',
    ];

    public const STATUS_ICONS = [
        self::STATUS_DRAFT => Heroicon::OutlinedPencilSquare,
        self::STATUS_CANCELED => Heroicon::OutlinedXCircle,
        self::STATUS_WAITING => Heroicon::OutlinedClock,
        self::STATUS_RECEIVED => Heroicon::OutlinedInboxArrowDown,
        self::STATUS_ORDERED => Heroicon::OutlinedShoppingCart,
        self::STATUS_FINISH => Heroicon::OutlinedCheckCircle,
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


            $year = now()->format('y');
            $month = now()->format('m');

            $warehouse = $record->warehouse->code;
            $company = $record->company->code;
            $division = $record->division->code;
            $project = $record->project->code;

            $prefix = "BPPB/{$year}/{$month}/{$warehouse}{$company}{$division}{$project}";

            $lastNumber = static::where('number', 'like', "{$prefix}/%")
                ->count() + 1;

            $sequence = str_pad($lastNumber, 3, '0', STR_PAD_LEFT);

            $record->number = "{$prefix}/{$sequence}";


            $record->status = self::STATUS_DRAFT;
        });


        static::updating(function ($record) {
            if ($record->isDirty() && $record->status !== self::STATUS_DRAFT) {
                $number = $record->number;

                $rev = 0;
                if (preg_match('/-Rev\.(\d+)$/', $number, $matches)) {
                    $rev = (int) $matches[1];
                }
                $rev++;
                $revNumber = str_pad($rev, 2, '0', STR_PAD_LEFT);

                $baseNumber = preg_replace('/-Rev\.\d+$/', '', $number);

                $record->number = "{$baseNumber}-Rev.{$revNumber}";
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
}
