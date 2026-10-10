<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Only Data Dictionary fields (M3 Batch 3 §11). */
class NoteResource extends JsonResource
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
            'author_id' => $this->author_id,
            'author_name' => $this->relationLoaded('author') ? $this->author?->name : null,
            'visibility' => $this->visibility,
            'body' => $this->body,
            'created_at' => self::iso($this->created_at),
        ];
    }
}
