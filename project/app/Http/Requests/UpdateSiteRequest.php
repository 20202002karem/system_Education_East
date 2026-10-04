<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::in(['school', 'department', 'warehouse'])],
            'code' => ['sometimes', 'string', 'max:30', Rule::unique('sites', 'code')->ignore($this->route('site'))],
            'name_ar' => ['sometimes', 'string', 'max:150'],
        ];
    }
}
