<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Only Data Dictionary fields (M3 Batch 3 §11). */
class ServiceRequestResource extends JsonResource
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
            'origin_site_id' => $this->origin_site_id,
            'origin_site_name' => $this->relationLoaded('originSite') ? $this->originSite?->name_ar : null,
            'asset_id' => $this->asset_id,
            'requester_user_id' => $this->requester_user_id,
            'requester_name' => $this->requester_name,
            'registered_by_user_id' => $this->registered_by_user_id,
            'channel_id' => $this->channel_id,
            'request_type_id' => $this->request_type_id,
            'suggested_priority' => $this->suggested_priority,
            'priority' => $this->priority,
            'description' => $this->description,
            'current_cycle_no' => $this->current_cycle_no,
            'reopen_count' => $this->reopen_count,
            'status' => $this->relationLoaded('currentCycle') ? $this->currentCycle?->status : null,
            'cycle' => $this->relationLoaded('currentCycle') && $this->currentCycle ? CycleResource::make($this->currentCycle)->resolve() : null,
            'cancellation' => $this->relationLoaded('cancellation') && $this->cancellation ? [
                'reason_category' => $this->cancellation->reason_category,
                'reason_text' => $this->cancellation->reason_text,
                'work_done_summary' => $this->cancellation->work_done_summary,
                'cancelled_by' => $this->cancellation->cancelled_by,
                'stage' => $this->cancellation->stage,
                'cancelled_at' => self::iso($this->cancellation->cancelled_at),
            ] : null,
            'created_at' => self::iso($this->created_at),
            'updated_at' => self::iso($this->updated_at),
        ];
    }
}
