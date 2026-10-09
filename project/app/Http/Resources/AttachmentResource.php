<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Only Data Dictionary fields (M3 Batch 3 §11). */
class AttachmentResource extends JsonResource
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
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'uploaded_by' => $this->uploaded_by,
            'sha256' => $this->sha256,
            'size_bytes' => $this->size_bytes,
            'mime_type' => $this->mime_type,
            'original_filename' => $this->original_filename,
            'created_at' => self::iso($this->created_at),
        ];
    }
}
