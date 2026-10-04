<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['school', 'department', 'warehouse'])],
            'code' => ['required', 'string', 'max:30', 'unique:sites,code'],
            'name_ar' => ['required', 'string', 'max:150'],
        ];
    }
}
