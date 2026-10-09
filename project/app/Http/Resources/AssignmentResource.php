<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Only Data Dictionary fields (M3 Batch 3 §11). */
class AssignmentResource extends JsonResource
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
            'cycle_id' => $this->cycle_id,
            'assignee_id' => $this->assignee_id,
            'assigned_by' => $this->assigned_by,
            'reason' => $this->reason,
            'from' => self::iso($this->from),
            'to' => self::iso($this->to),
        ];
    }
}
