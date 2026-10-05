<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Serializes the three append-only history tables with exactly their dictionary columns. */
class AssetHistoryResource extends JsonResource
{
    public static $wrap = null;

    private const COLUMNS = [
        'asset_status_history' => ['id', 'from_status', 'to_status', 'changed_by', 'reason', 'changed_at'],
        'asset_identifier_corrections' => ['id', 'field_name', 'old_value', 'new_value', 'corrected_by', 'reason', 'corrected_at'],
        'asset_legacy_numbers' => ['id', 'legacy_number', 'source', 'added_by', 'added_at'],
    ];

    public function toArray(Request $request): array
    {
        $out = [];
        foreach (self::COLUMNS[$this->resource->getTable()] as $c) {
            $v = $this->resource->{$c};
            $out[$c] = $v instanceof \DateTimeInterface ? \Carbon\Carbon::instance($v)->utc()->toIso8601ZuluString() : $v;
        }

        return $out;
    }
}
