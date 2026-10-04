<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditChainCheck extends Model
{
    protected $fillable = ['run_at', 'from_seq', 'to_seq', 'result'];

    protected function casts(): array
    {
        return ['run_at' => 'datetime'];
    }
}
