<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** DD-2: chairman only, never opened by edit_assets. */
class CorrectAssetIdentifierRequest extends AssetWriteRequest
{
    protected function allowed(): bool
    {
        return $this->user()->isChairman();
    }

    public function rules(): array
    {
        return [
            'field_name' => ['required', Rule::in(['inventory_no', 'serial_no'])],
            'new_value' => ['required', 'string', 'max:'.($this->input('field_name') === 'inventory_no' ? 30 : 60)],
            'reason' => ['required', 'string', 'max:255', 'regex:/\S/'],
        ];
    }
}
