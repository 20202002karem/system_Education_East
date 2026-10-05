<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Only Data Dictionary fields (Batch 3 §17). Manager is derived (D-28), never stored. */
class AssetResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventory_no' => $this->inventory_no,
            'serial_no' => $this->serial_no,
            'category_id' => $this->category_id,
            'current_site_id' => $this->current_site_id,
            'status' => $this->status,
            'holder_text' => $this->holder_text,
            'version' => $this->version,
            'created_at' => $this->created_at?->utc()->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->utc()->toIso8601ZuluString(),
        ];
    }
}
