<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only (no update/delete). visibility=internal is never exposed to school_manager (BR-M3-05). */
class RequestNote extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['cycle_id', 'author_id', 'visibility', 'body'];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(RequestCycle::class, 'cycle_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
