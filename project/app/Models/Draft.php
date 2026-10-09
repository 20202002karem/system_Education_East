<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** The only physically deletable M3 entity (DBD-M3-04). */
class Draft extends Model
{
    public const CREATED_AT = null;

    protected $fillable = ['user_id', 'form_type', 'form_key', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
