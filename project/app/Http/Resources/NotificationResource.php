<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Only Data Dictionary fields (M3 Batch 3 §11). */
class NotificationResource extends JsonResource
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
            'event_type' => $this->event_type,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'message' => $this->message,
            'read_at' => self::iso($this->read_at),
            'created_at' => self::iso($this->created_at),
        ];
    }
}
