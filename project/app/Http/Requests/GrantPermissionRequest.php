<?php

namespace App\Http\Requests;

use App\Models\PermissionGrant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Batch 3 §3.4 — create_request is reserved/disabled (D-22b) and therefore
 * excluded from PermissionGrant::KEYS entirely, so Rule::in already rejects it with 422.
 */
class GrantPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_key' => ['required', Rule::in(PermissionGrant::KEYS)],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
