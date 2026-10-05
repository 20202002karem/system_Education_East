<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Validation\Rule;

class ChangeAssetStatusRequest extends AssetWriteRequest
{
    public function rules(): array
    {
        return [
            'to_status' => ['required', 'string', Rule::in(Asset::MANUAL_STATUSES)],
            'reason' => ['nullable', 'string', 'max:255'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }
}
