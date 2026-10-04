<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only. No FK relations by design (schema independence). Never call
 * update()/delete() on this model anywhere in the codebase — writes go only
 * through App\Services\AuditLogger::record().
 */
class AuditLog extends Model
{
    protected $table = 'audit_log';
    protected $primaryKey = 'seq';
    public $timestamps = false;

    protected $fillable = [
        'occurred_at', 'actor_id', 'action', 'entity_type', 'entity_id',
        'before', 'after', 'reason', 'source', 'ip', 'flags', 'prev_hash', 'hash',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'before' => 'array',
            'after' => 'array',
            'flags' => 'array',
        ];
    }
}
