<?php

namespace App\Models;

use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
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

    public function itemCategories(): BelongsToMany
    {
        return $this->belongsToMany(ItemCategory::class);
    }
}
