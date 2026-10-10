<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    public const STATUSES = ['new', 'assigned', 'in_progress', 'held', 'completed', 'cancelled'];
    public const NON_TERMINAL = ['new', 'assigned', 'in_progress', 'held'];

    protected $fillable = [
        'ref_no', 'task_type_id', 'title', 'description', 'request_cycle_id', 'asset_id', 'site_id', 'assignee_id',
        'status', 'due_at', 'result_summary', 'created_by', 'version',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TaskType::class, 'task_type_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(RequestCycle::class, 'request_cycle_id');
    }

    public function isTerminal(): bool
    {
        return ! in_array($this->status, self::NON_TERMINAL, true);
    }
}
