<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * `status` and `mobile_verified_at` are deliberately NOT mass assignable:
     * they only change through activate() / explicit assignment.
     */
    protected $fillable = [
        'name',
        'mobile',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'mobile_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function expertProfile(): HasOne
    {
        return $this->hasOne(ExpertProfile::class);
    }

    public function ownerProfile(): HasOne
    {
        return $this->hasOne(OwnerProfile::class);
    }

    public function isMobileVerified(): bool
    {
        return ! is_null($this->mobile_verified_at);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function hasPassword(): bool
    {
        return filled($this->password);
    }

    public function primaryRole(): ?Role
    {
        $name = $this->roles->first()?->name;

        return $name ? Role::tryFrom($name) : null;
    }

    public function requiresTwoFactor(): bool
    {
        return $this->hasAnyRole(Role::twoFactorValues());
    }

    /** Mark the mobile as verified and open the account. */
    public function activate(): void
    {
        $this->forceFill([
            'mobile_verified_at' => $this->mobile_verified_at ?? now(),
            'status' => UserStatus::Active,
        ])->save();
    }

    /** Log this user out of every device (database session driver only). */
    public function forgetSessions(): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $this->getKey())->delete();
        }
    }
}
