<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'key', 'response_snapshot', 'response_status', 'created_at'];

    protected function casts(): array
    {
        return ['response_snapshot' => 'array', 'created_at' => 'datetime'];
    }
}
