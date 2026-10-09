<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Only Data Dictionary fields (M3 Batch 3 §11). */
class CycleResource extends JsonResource
{
    public static $wrap = null;

    protected static function iso($v): ?string
    {
        return $v?->copy()->utc()->toIso8601ZuluString();
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_id' => $this->request_id,
            'cycle_no' => $this->cycle_no,
            'status' => $this->status,
            'assignee_user_id' => $this->assignee_user_id,
            'assignee_name' => $this->relationLoaded('assignee') ? $this->assignee?->name : null,
            'started_at' => self::iso($this->started_at),
            'closed_at' => self::iso($this->closed_at),
            'closure_action' => $this->closure_action,
            'closure_result' => $this->closure_result,
            'closure_effort_minutes' => $this->closure_effort_minutes,
            'hold_reason' => $this->hold_reason,
            'version' => $this->version,
            'created_at' => self::iso($this->created_at),
            'updated_at' => self::iso($this->updated_at),
        ];
    }
}
