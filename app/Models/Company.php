<?php

namespace App\Models;

use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
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

        'alias',
        'address',
        'city',
        'post_code',
        'contact_person',
        'contact_person_position',
        'phone',
        'fax',
        'email',
        'website',
        'tax_number',

        'is_active',
    ];

    protected array $defaultEmptyStringFields = [
        'description',

        'post_code',
        'contact_person',
        'contact_person_position',
        'phone',
        'fax',
        'email',
        'website',
        'tax_number',
    ];

    /* ================= RELATION ================= */

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class)->orderBy('name')->orderBy('code');
    }

    public function divisions(): BelongsToMany
    {
        return $this->belongsToMany(Division::class)->orderBy('name')->orderBy('code');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->orderBy('name')->orderBy('code');
    }

    public function banks(): HasMany
    {
        return $this->hasMany(Bank::class)->orderBy('name')->orderBy('code');
    }
}
