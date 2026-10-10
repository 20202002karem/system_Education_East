<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Only Data Dictionary fields (M3 Batch 3 §11). */
class DraftResource extends JsonResource
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
            'form_type' => $this->form_type,
            'form_key' => $this->form_key,
            'payload' => $this->payload,
            'updated_at' => self::iso($this->updated_at),
        ];
    }
}
