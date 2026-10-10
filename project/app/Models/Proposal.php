<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['type', 'subject_type', 'subject_id', 'proposer_id', 'reason', 'work_done_summary', 'status', 'decided_by', 'decided_at'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }
}
