<?php

namespace App\Models;

use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as RoleModel;

class Role extends RoleModel
{
    use SoftDeletes;
    use LogsAllFillable;

    public const PROJECT_OWNER = 'Project Owner';
    public const ADMINISTRATOR = 'Administrator';
    public const LOGISTIC = 'Logistic';
    public const LOGISTIC_MANAGER = 'Logistic Manager';
    public const PURCHASING = 'Purchasing';
    public const PURCHASING_MANAGER = 'Purchasing Manager';
    public const QUANTITY_SURVEYOR = 'Quantity Surveyor';
    public const AUDIT = 'Audit';
    public const AUDIT_MANAGER = 'Audit Manager';

    public const ALL = [
        self::PROJECT_OWNER,
        self::ADMINISTRATOR,
        self::LOGISTIC,
        self::LOGISTIC_MANAGER,
        self::PURCHASING,
        self::PURCHASING_MANAGER,
        self::QUANTITY_SURVEYOR,
        self::AUDIT,
        self::AUDIT_MANAGER,
    ];

    // Roles yang dianggap super
    public const SUPER_ROLES = [
        self::PROJECT_OWNER,
        self::ADMINISTRATOR,
    ];

    protected $fillable = [
        'name',
        'guard_name',
    ];

    protected static function booted()
    {
        static::creating(function ($role) {
            if (blank($role->guard_name)) {
                $role->guard_name = "web";
            }
        });
    }
}
