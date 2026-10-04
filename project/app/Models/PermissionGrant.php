<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionGrant extends Model
{
    use HasFactory;

    public const KEYS = ['initiate_transfer', 'initiate_decommission', 'edit_assets'];

    protected $fillable = [
        'user_id', 'permission_key', 'granted_by', 'granted_at',
        'revoked_by', 'revoked_at', 'reason',
    ];

    protected function casts(): array
    {
        return ['granted_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isActive(): bool
    {
        return is_null($this->revoked_at);
    }
}
