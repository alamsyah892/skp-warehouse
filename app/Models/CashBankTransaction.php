<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class CashBankTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\CashBankTransactionFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString;

    public const NUMBER_PREFIX = 'CBT';

    protected $fillable = [
        'type',
        'company_id',
        'bank_id',
        'vendor_id',
        'project_id',
        'number',
        'date',
        'amount',
        'check_number',
        'description',
        'notes',
        'info',
    ];

    protected array $defaultEmptyStringFields = [
        'check_number',
        'description',
        'notes',
        'info',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $record): void {
            if (blank($record->number)) {
                $record->number = self::generateNumber($record->date);
            }

            $record->date ??= now()->toDateString();
        });
    }

    public static function generateNumber(?\DateTimeInterface $date = null): string
    {
        $date ??= now();
        $year = $date->format('Y');
        $prefix = self::NUMBER_PREFIX . "-{$year}-";

        return DB::transaction(function () use ($prefix): string {
            $lastNumber = self::withTrashed()
                ->where('number', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('number')
                ->value('number');

            $sequence = 1;

            if ($lastNumber !== null && preg_match('/-(\d+)$/', $lastNumber, $matches)) {
                $sequence = (int) $matches[1] + 1;
            }

            return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
