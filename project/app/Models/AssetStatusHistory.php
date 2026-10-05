<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only (Batch 2 §7): rows are inserted once and never updated or deleted. */
class AssetStatusHistory extends Model
{
    protected $table = 'asset_status_history';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('AssetStatusHistory is append-only.'));
        static::deleting(fn () => throw new \LogicException('AssetStatusHistory is append-only.'));
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
