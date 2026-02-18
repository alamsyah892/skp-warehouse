<?php

namespace App\Models;

use App\Models\Concerns\LogsAllFillable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as RoleModel;

class Role extends RoleModel
{
    use SoftDeletes;
    use LogsAllFillable;

    // Roles yang dianggap super
    public const SUPER_ROLES = [
        'Project Owner',
        'Administrator',
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
