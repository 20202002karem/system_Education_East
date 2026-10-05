<?php

namespace App\Http\Requests;

class UpdateAssetRequest extends AssetWriteRequest
{
    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'category_id' => ['sometimes', 'integer'],
            'holder_text' => ['sometimes', 'nullable', 'string', 'max:150'],
            'inventory_no' => ['prohibited'],
            'serial_no' => ['prohibited'],
            'status' => ['prohibited'],
            'current_site_id' => ['prohibited'],
        ];
    }
}
