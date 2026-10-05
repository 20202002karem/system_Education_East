<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\AssetAccess;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AssetAccess::canEdit($this->user());
    }

    public function rules(): array
    {
        return [
            'inventory_no' => ['required', 'string', 'max:30'],
            'serial_no' => ['nullable', 'string', 'max:60'],
            'category_id' => ['required', 'integer'],
            'current_site_id' => ['required', 'integer'],
            'holder_text' => ['nullable', 'string', 'max:150'],
            'legacy_numbers' => ['nullable', 'array'],
            'legacy_numbers.*' => ['required', 'string', 'max:60'],
            'status' => ['prohibited'],
        ];
    }
}
