<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    public const MANUAL_STATUSES = ['working', 'under_maintenance', 'broken', 'stored'];
    public const RESERVED_STATUSES = ['in_transfer', 'decommissioned']; // M4 only, never settable in M2
    public const STATUSES = ['working', 'under_maintenance', 'broken', 'stored', 'in_transfer', 'decommissioned'];

    protected $fillable = ['inventory_no', 'serial_no', 'category_id', 'current_site_id', 'status', 'holder_text', 'version'];

    protected function casts(): array
    {
        return ['version' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DeviceCategory::class, 'category_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'current_site_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(AssetStatusHistory::class);
    }

    public function identifierCorrections(): HasMany
    {
        return $this->hasMany(AssetIdentifierCorrection::class);
    }

    public function legacyNumbers(): HasMany
    {
        return $this->hasMany(AssetLegacyNumber::class);
    }
}
