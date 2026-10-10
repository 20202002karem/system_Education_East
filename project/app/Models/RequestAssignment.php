<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Append-only history (from/to are the only mutable columns: `to` is set when superseded). */
class RequestAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = ['cycle_id', 'assignee_id', 'assigned_by', 'reason', 'from', 'to'];

    protected function casts(): array
    {
        return ['from' => 'datetime', 'to' => 'datetime'];
    }
}
