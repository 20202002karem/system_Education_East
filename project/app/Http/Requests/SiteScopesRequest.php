<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SiteScopesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_ids' => ['required', 'array'],
            'site_ids.*' => ['integer', 'exists:sites,id'],
        ];
    }
}
