<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Batch 3 §3.2 — POST /users. role/view_scope enums are closed lists;
 * site_scope_ids required iff view_scope=sites (422 otherwise).
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by role:chairman route middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'login_identifier' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in(User::ROLES)],
            'view_scope' => ['required', Rule::in(User::VIEW_SCOPES)],
            'specialization' => ['nullable', 'string', 'max:150'],
            'site_scope_ids' => ['required_if:view_scope,sites', 'array'],
            'site_scope_ids.*' => ['integer', 'exists:sites,id'],
            'password' => ['nullable', 'string', 'min:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'site_scope_ids.required_if' => 'مطلوب عند view_scope=sites',
        ];
    }
}
