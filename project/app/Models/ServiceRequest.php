<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** M3 `requests` table (class renamed to avoid clashing with Illuminate Request). BR-M3-17: origin_site_id is immutable. */
class ServiceRequest extends Model
{
    protected $table = 'requests';

    protected $fillable = [
        'ref_no', 'origin_site_id', 'asset_id', 'requester_user_id', 'requester_name', 'registered_by_user_id',
        'channel_id', 'request_type_id', 'suggested_priority', 'priority', 'description', 'current_cycle_no', 'reopen_count',
    ];

    public const PRIORITIES = ['emergency', 'high', 'normal', 'low'];

    public function cycles(): HasMany
    {
        return $this->hasMany(RequestCycle::class, 'request_id');
    }

    public function currentCycle(): HasOne
    {
        return $this->hasOne(RequestCycle::class, 'request_id')->ofMany('cycle_no', 'max');
    }

    public function cancellation(): HasOne
    {
        return $this->hasOne(RequestCancellation::class, 'request_id');
    }

    public function originSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'origin_site_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    protected static function booted(): void
    {
        static::updating(function (self $m) {
            if ($m->isDirty('origin_site_id')) {
                throw new \LogicException('origin_site_id is immutable (BR-M3-17).');
            }
        });
    }
}
