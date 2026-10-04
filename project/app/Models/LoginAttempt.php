<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'attempted_identifier', 'success', 'ip', 'attempted_at'];

    protected function casts(): array
    {
        return ['success' => 'boolean', 'attempted_at' => 'datetime'];
    }
}
