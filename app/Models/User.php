<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Andreia\FilamentUiSwitcher\Models\Traits\HasUiPreferences;
use App\Models\Concerns\LogsAllFillable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles;
    use LogsAllFillable;
    use TwoFactorAuthenticatable, HasUiPreferences;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted()
    {
        static::creating(function ($user) {
            if (blank($user->avatar_url)) {
                $name = urlencode($user->name);

                $user->avatar_url = "https://ui-avatars.com/api/?name={$name}&background=random&color=fff";
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',

            'ui_preferences' => 'array',
        ];
    }

    public function getFilamentAvatarUrl(): ?string
    {
        // 1. Jika kosong, pakai UI Avatars
        if (!$this->avatar_url) {
            $name = urlencode($this->name);
            return "https://ui-avatars.com/api/?name={$name}&background=random&color=fff";
        }

        // 2. Jika sudah berupa URL lengkap (http/https), langsung return
        if (str_starts_with($this->avatar_url, 'http')) {
            return $this->avatar_url;
        }

        // 3. Jika hanya path (misal: 'avatars/user1.jpg'), gunakan Storage
        return Storage::url($this->avatar_url);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /* ================= RELATION ================= */

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class)->orderBy('name')->orderBy('code');
    }
}
