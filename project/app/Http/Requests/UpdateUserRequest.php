<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'role' => ['sometimes', Rule::in(User::ROLES)],
            'view_scope' => ['sometimes', Rule::in(User::VIEW_SCOPES)],
            'specialization' => ['sometimes', 'nullable', 'string', 'max:150'],
            'site_scope_ids' => ['required_if:view_scope,sites', 'array'],
            'site_scope_ids.*' => ['integer', 'exists:sites,id'],
        ];
    }
}
