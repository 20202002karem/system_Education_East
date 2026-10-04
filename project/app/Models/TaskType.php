<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskType extends Model
{
    protected $fillable = ['name', 'is_administrative', 'secretary_assignable'];

    protected function casts(): array
    {
        return ['is_administrative' => 'boolean', 'secretary_assignable' => 'boolean'];
    }
}
