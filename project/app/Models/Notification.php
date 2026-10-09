<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Internal notifications only (DD-M3-04): no email, push or SMS. */
class Notification extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['recipient_id', 'event_type', 'source_type', 'source_id', 'message', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
