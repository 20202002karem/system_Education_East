<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Only Data Dictionary fields (M3 Batch 3 §11). */
class TaskResource extends JsonResource
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
            'ref_no' => $this->ref_no,
            'task_type_id' => $this->task_type_id,
            'title' => $this->title,
            'description' => $this->description,
            'request_cycle_id' => $this->request_cycle_id,
            'asset_id' => $this->asset_id,
            'site_id' => $this->site_id,
            'assignee_id' => $this->assignee_id,
            'status' => $this->status,
            'due_at' => self::iso($this->due_at),
            'result_summary' => $this->result_summary,
            'created_by' => $this->created_by,
            'version' => $this->version,
            'created_at' => self::iso($this->created_at),
            'updated_at' => self::iso($this->updated_at),
        ];
    }
}
