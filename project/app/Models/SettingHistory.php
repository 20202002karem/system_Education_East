<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingHistory extends Model
{
    protected $table = 'settings_history';

    public $timestamps = false;

    protected $fillable = ['key', 'old_value', 'new_value', 'updated_by', 'changed_at'];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }
}
