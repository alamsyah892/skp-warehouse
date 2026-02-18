<?php

namespace App\Models;

use App\Models\Concerns\DefaultEmptyString;
use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemCategory extends Model
{
    use SoftDeletes;
    use LogsAllFillable, DefaultEmptyString;

    public const LEVEL_DOMAIN = 1;
    public const LEVEL_CATEGORY = 2;
    public const LEVEL_SUB_CATEGORY = 3;
    // public const LEVEL_FINAL_CATEGORY = 4;

    public const LEVEL_LABELS = [
        self::LEVEL_DOMAIN => 'Domain',
        self::LEVEL_CATEGORY => 'Category',
        self::LEVEL_SUB_CATEGORY => 'Sub Category',
        // self::LEVEL_FINAL_CATEGORY => 'Final Category',
    ];

    public const LEVEL_COLOR = [
        self::LEVEL_DOMAIN => 'success',
        self::LEVEL_CATEGORY => 'warning',
        self::LEVEL_SUB_CATEGORY => 'danger',
        // self::LEVEL_FINAL_CATEGORY => 'info',
    ];

    public $fillable = [
        'parent_id',
        'level',

        'code',
        'name',
        'description',

        'allow_po',
    ];

    protected array $defaultEmptyStringFields = [
        'code',
        'description',
    ];

    /* ================= RELATION ================= */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    // recursive children
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id');
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class);
    }


    /* ================= ACCESSOR ================= */

    public function getParentPathAttribute(): string
    {
        if (!$this->parent) {
            return '';
        }

        return $this->parent->parent_path
            ? $this->parent->parent_path . ' → ' . $this->parent->name
            : $this->parent->name;
    }

    public function getParentFullPathAttribute(): string
    {
        $names = [];
        $current = $this;

        while ($current) {
            array_unshift($names, $current->name);
            $current = $current->parent;
        }

        return implode(' → ', $names);
    }


    /* ================= HELPER ================= */

    public function isLeaf(): bool
    {
        return !$this->children()->exists();
    }
}
