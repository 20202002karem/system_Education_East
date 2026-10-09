<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Only Data Dictionary fields (M3 Batch 3 §11). */
class ProposalResource extends JsonResource
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
            'type' => $this->type,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'proposer_id' => $this->proposer_id,
            'reason' => $this->reason,
            'work_done_summary' => $this->work_done_summary,
            'status' => $this->status,
            'decided_by' => $this->decided_by,
            'decided_at' => self::iso($this->decided_at),
            'created_at' => self::iso($this->created_at),
        ];
    }
}
