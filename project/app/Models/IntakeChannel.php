<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntakeChannel extends Model
{
    protected $table = 'intake_channels';

    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
