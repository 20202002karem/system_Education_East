<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestCancellation extends Model
{
    public $timestamps = false;

    protected $fillable = ['request_id', 'reason_category', 'reason_text', 'work_done_summary', 'cancelled_by', 'stage', 'cancelled_at'];

    protected function casts(): array
    {
        return ['cancelled_at' => 'datetime'];
    }
}
