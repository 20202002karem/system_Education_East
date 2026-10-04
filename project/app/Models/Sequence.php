<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['scope', 'year', 'last_value'];
}
