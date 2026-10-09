<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestCycle extends Model
{
    public const STATUSES = ['new', 'triaged', 'assigned', 'in_progress', 'held', 'reopened_pending_assignment', 'closed', 'cancelled'];
    public const TERMINAL = ['closed', 'cancelled'];

    protected $fillable = [
        'request_id', 'cycle_no', 'status', 'assignee_user_id', 'started_at', 'closed_at', 'closure_action',
        'closure_result', 'closure_effort_minutes', 'hold_reason', 'open_marker', 'version',
    ];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RequestAssignment::class, 'cycle_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'request_cycle_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL, true);
    }
}
