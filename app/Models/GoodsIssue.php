<?php

namespace App\Models;

use App\Enums\GoodsIssueStatus;
use App\Enums\GoodsIssueType;
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

class GoodsIssue extends Model
{
    /** @use HasFactory<\Database\Factories\GoodsIssueFactory> */
    use HasFactory;
    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString, HasDocumentNumber, HasStateMachine, HasDocumentRevision;

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
        'notes',
        'info',
        'status',
    ];

    protected array $defaultEmptyStringFields = [
        'number',
        'description',
        'notes',
        'info',
    ];

    protected $casts = [
        'type' => GoodsIssueType::class,
        'status' => GoodsIssueStatus::class,
    ];

    public const MODEL_ALIAS = 'GI';

    protected static function booted(): void
    {
        static::addGlobalScope('user_warehouses', function (Builder $builder): void {
            if ($user = auth()->user()) {
                $userWarehouseIds = $user->warehouses()->pluck('warehouses.id');

                if ($userWarehouseIds->isNotEmpty()) {
                    $builder->whereIn('warehouse_id', $userWarehouseIds);
                }
            }
        });

        static::creating(function (self $record): void {
            $record->user_id = auth()->id();
            $record->status = GoodsIssueStatus::ISSUED;
            $record->type ??= GoodsIssueType::ISSUE;

            $record->loadMissing([
                'division',
                'project',
            ]);

            $record->number = self::generateNumber($record);
        });

        static::created(function (self $record): void {
            $record->setStatusLog(GoodsIssueStatus::ISSUED);
        });
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

    public function goodsIssueItems(): HasMany
    {
        return $this->hasMany(GoodsIssueItem::class)->orderBy('sort');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(GoodsIssueStatusLog::class);
    }

    public function hasStatus(GoodsIssueStatus $status): bool
    {
        return $this->status === $status;
    }

    public function getTotalIssuedQty(): float
    {
        return (float) ($this->getAttribute('goods_issue_items_sum_qty') ?? 0);
    }

    public function scopeWithQuantitySummary(Builder $query): Builder
    {
        return $query->withSum('goodsIssueItems', 'qty');
    }

    protected function getStatusEnumClass(): string
    {
        return GoodsIssueStatus::class;
    }

    protected function canUserTransition($newStatus, $user, array $flow): bool
    {
        if (
            $this->hasStatus(GoodsIssueStatus::ISSUED) &&
            $newStatus === GoodsIssueStatus::CANCELED &&
            $this->user_id === $user->id
        ) {
            return true;
        }

        return $user->hasAnyRole($flow[$newStatus->value] ?? []);
    }

    protected function getWatchedFields(): array
    {
        return [
            'warehouse_address_id',
            'description',
            // 'type',
        ];
    }

    protected function getRevisionItemsRelation(): ?string
    {
        return 'goodsIssueItems';
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
