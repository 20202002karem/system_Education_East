<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * users (Batch 2 §B3). login_identifier = email (product decision, closing
 * the "[مفتوح]" in Batch 2). mfa_secret is encrypted and NEVER placed in
 * $fillable, never appended to arrays/JSON, and excluded via $hidden.
 */
class User extends Model implements AuthenticatableContract
{
    use HasApiTokens, HasFactory, Authenticatable;

    public const ROLES = ['school_manager', 'chairman', 'secretary', 'engineer', 'technician'];
    public const VIEW_SCOPES = ['own_site', 'sites', 'all', 'assigned_only'];

    protected $fillable = [
        'name', 'email', 'role', 'view_scope', 'status', 'specialization',
    ];

    // password_hash, mfa_secret, mfa_enabled, failed_login_count, locked_until
    // are NEVER mass-assignable — only ever written by dedicated service methods.
    protected $guarded = [
        'password_hash', 'mfa_secret', 'mfa_enabled', 'failed_login_count', 'locked_until',
    ];

    protected $hidden = [
        'password_hash', 'mfa_secret', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'mfa_enabled' => 'boolean',
            'mfa_secret' => 'encrypted', // AES-256-CBC via APP_KEY; never returned by toArray()/toJson()
            'locked_until' => 'datetime',
        ];
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function siteScopes(): HasMany
    {
        return $this->hasMany(UserSiteScope::class);
    }

    public function permissionGrants(): HasMany
    {
        return $this->hasMany(PermissionGrant::class);
    }

    public function activePermissionGrants()
    {
        return $this->permissionGrants()->whereNull('revoked_at');
    }

    public function hasActivePermission(string $key): bool
    {
        return $this->activePermissionGrants()->where('permission_key', $key)->exists();
    }

    public function isChairman(): bool
    {
        return $this->role === 'chairman';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
