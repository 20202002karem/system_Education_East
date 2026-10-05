<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only (Batch 2 §7): rows are inserted once and never updated or deleted. */
class AssetLegacyNumber extends Model
{
    protected $table = 'asset_legacy_numbers';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['added_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('AssetLegacyNumber is append-only.'));
        static::deleting(fn () => throw new \LogicException('AssetLegacyNumber is append-only.'));
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
