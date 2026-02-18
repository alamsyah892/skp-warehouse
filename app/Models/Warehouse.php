<?php

namespace App\Models;

use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    /** @use HasFactory<\Database\Factories\VendorFactory> */
    use HasFactory;

    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString;

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_INACTIVE => 'Inactive',
    ];

    protected $fillable = [
        'code',
        'name',
        'description',

        'is_active',
    ];

    protected array $defaultEmptyStringFields = [
        'description',
    ];

    /* ================= RELATION ================= */

    public function addresses(): HasMany
    {
        return $this->hasMany(WarehouseAddress::class, 'warehouse_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->orderBy('name')->orderBy('code');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->orderBy('name')->orderBy('code');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->orderBy('name');
    }
}
